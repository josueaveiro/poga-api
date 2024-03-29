<?php

namespace Raffles\Modules\Poga\Http\Controllers\Base97;

use Raffles\Http\Controllers\Controller;
use Raffles\Modules\Poga\Models\User;
use Raffles\Modules\Poga\Notifications\{ PagoConfirmadoAcreedor, PagoConfirmadoDeudor, PagoConfirmadoParaAdminPoga };
use Raffles\Modules\Poga\Repositories\PagareRepository;
use Raffles\Modules\Poga\UseCases\{ ActualizarBoletaPago, ActualizarEstadoPagare, GenerarBoletaPago };

use Carbon\Carbon;
use Illuminate\Http\Request;
use Log;
use RafflesArgentina\ResourceController\Traits\FormatsValidJsonResponses;

class NotificacionPagoController extends Controller
{
    use FormatsValidJsonResponses;

    /**
     * The PagareRepository object.
     *
     * @var PagareRepository 
     */
    protected $repository;

    /**
     * Create a new NotificacionPagoController instance.
     *
     * @param PagareRepository $repository
     *
     * @return void
     */
    public function __construct(PagareRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $b64UrlModel = $request->doc_model;
        $jsonModel =  base64_decode(str_replace(['-','_'], ['+','/'], $b64UrlModel));
        $docModel = json_decode($jsonModel, true);

        try {
            Log::info('Notificación de pago:');
            Log::info($docModel);
        } catch (\Exception $e) {
            //
        }

        // Buscamos el pagare asociado a la boleta.
        $pagareBoleta = $this->repository->find($docModel['docId']);

	if (!$pagareBoleta) {
            $this->validNotFoundJsonResponse();
        }

        $this->handlePayStatus($docModel, $pagareBoleta);

        return $this->validSuccessJsonResponse();
    }

    /**
     * @param array  $docModel     La notificación de pago.
     * @param Pagare $pagareBoleta El pagare asociado a la boleta.
     *
     * @return JsonResponse
     */
    protected function handlePayStatus($docModel, $pagareBoleta)
    {
        $payStatus = $docModel['payStatus']['status'];
        $items = $docModel['description']['items'];

        switch ($payStatus) {
        case 'success':
            $adminUser = User::where('email', env('MAIL_ADMIN_ADDRESS'))->first();

            // Si el pagaré está en dólares.
	    if ($pagareBoleta->id_moneda == 2) {
		try {
                    $cotizacion = $docModel['txList'][0]['exchangeRate']['value'];
		} catch (\Exception $e) {
		    // En caso de que no pueda traer la cotización.
                    $cotizacion = 0;
		}	
            } else {
                $cotizacion = 0;
            }

            if ($items) {
                // Si la boleta tiene ítems.
                foreach ($items as $item) {
                    // Busca el pagaré asociado al ítem.    
                    $pagareItem = $this->repository->findOrFail($item['code']);
           
                    // Actualiza la cotización, el estado y la fecha de pago a confirmar del pagaré asociado al ítem.
                    $this->repository->update($pagareItem, ['cotizacion' => $cotizacion, 'enum_estado' => 'PAGADO', 'fecha_pago_a_confirmar' => Carbon::today()]);

                    // Para cada ítem crea un pagaré de comisión.
                    $this->repository->create(
                        [
                        'descripcion' => 'Comisión POGA (%5.5)',
                        'enum_clasificacion_pagare' => 'COMISION_POGA',
                        'enum_estado' => 'PENDIENTE',
                        'fecha_pagare' => Carbon::now(),
                        'fecha_vencimiento' => Carbon::now()->addYear(),
                        'id_inmueble' => $pagareItem->id_inmueble,
                        'id_moneda' => $pagareItem->id_moneda,
                        'id_persona_acreedora' => $adminUser->id_persona,
                        'id_persona_deudora' => $pagareItem->id_persona_acreedora,
                        'id_tabla' => $pagareItem->id,
                        'monto' => $pagareItem->monto * 5.5 / 100
                        ]
                    );
                }
            } else {
                // Si la boleta no tiene ítems crea solo un pagaré de comisión.
                $this->repository->create(
                    [
                    'descripcion' => 'Comisión POGA (%5.5)',
                    'enum_clasificacion_pagare' => 'COMISION_POGA',
                    'enum_estado' => 'PENDIENTE',
                    'fecha_pagare' => Carbon::now(),
                    'fecha_vencimiento' => Carbon::now()->addYear(),
                    'id_inmueble' => $pagareBoleta->id_inmueble,
                    'id_moneda' => $pagareBoleta->id_moneda,
                    'id_persona_acreedora' => $adminUser->id_persona,
                    'id_persona_deudora' => $pagareBoleta->id_persona_acreedora,
                    'id_tabla' => $pagareBoleta->id,
                    'monto' => $pagareBoleta->monto * 5.5 / 100
                    ]
                );
            }

            // Actualiza la cotización, el estado y la fecha de pago a confirmar del pagaré asociado a la boleta.
            $pagareBoleta = $this->repository->update($pagareBoleta, ['cotizacion' => $cotizacion, 'enum_estado' => 'PAGADO', 'fecha_pago_a_confirmar' => Carbon::today()])[1];

            // Válido entre el 1 de Abril y 30 de Junio.
            if ($pagareBoleta->enum_opcion_pago === 'MANUAL' ||  $pagareBoleta->enum_opcion_pago === 'MINIMO') {
                $pagarePagoDiferido = $this->repository->where('id_tabla', $pagareBoleta->id_tabla)->where('enum_estado', 'PENDIENTE')->where('enum_clasificacion_pagare', 'PAGO_DIFERIDO')->first();
                if (!$pagarePagoDiferido) {
                    $this->generarPagoDiferido($pagareBoleta);
                } else {
                    $this->actualizarPagoDiferido($pagareBoleta, $pagarePagoDiferido);
                }

                $this->actualizarMontoPagare($pagareBoleta);
            }

            // Dispara notificaciones al acreedor, deudor y al admin de Poga.
            try {
                $pagareBoleta->idPersonaAcreedora->user->notify(new PagoConfirmadoAcreedor($pagareBoleta, $docModel));
            } catch (\Exception $e) {
                $pagareBoleta->idPersonaDeudora->user->notify(new PagoConfirmadoDeudor($pagareBoleta, $docModel));
            }

            try {
                $adminUser->notify(new PagoConfirmadoParaAdminPoga($pagareBoleta, $docModel));
            } catch(\Exception $e) {

            }

            break;
        case 'pending':
            // Si el estado del pagare no es PENDIENTE se trata de una reversión.
            if ($pagareBoleta->enum_estado !== 'PENDIENTE') {    
                $this->repository->update($pagareBoleta, ['enum_estado' => 'PENDIENTE', 'revertido' => '1']);
        
                if ($items) {
                    foreach ($items as $item) {
                        $this->repository->update($item['code'], ['enum_estado' => 'PENDIENTE', 'revertido' => '1']);
                    }
                }
            }
            break;
        }
    }

    // Válido entre el 1 de Abril y 30 de Junio.
    protected function actualizarPagoDiferido($pagareBoleta, $pagarePagoDiferido) {
        $pagarePagoDiferido = $this->repository->update($pagarePagoDiferido, [
            'monto' => $pagarePagoDiferido->monto + ($pagareBoleta->monto - ($pagareBoleta->enum_opcion_pago === 'MANUAL' ? $pagareBoleta->monto_manual : $pagareBoleta->monto_minimo)),
        ])[1];

        $datosBoleta = [
            'debt' => [
                'amount' => [
                    'currency' => $pagarePagoDiferido->id_moneda == 1 ? 'PYG' : 'USD',
                    'value' => $pagarePagoDiferido->monto,
                ],
            ]
        ];

        $boleta = $this->dispatchNow(new ActualizarBoletaPago($pagarePagoDiferido->id, $datosBoleta));
    }

    // Válido entre el 1 de Abril y 30 de Junio.
    protected function generarPagoDiferido($pagare) {
        $summary = 'Pago diferido #'.$pagare->id;

        $pagarePagoDiferido = $this->repository->create([
            'descripcion' => $summary,
            'enum_clasificacion_pagare' => 'PAGO_DIFERIDO',
            'enum_estado' => 'PENDIENTE',
            'fecha_pagare' => Carbon::now()->startOfDay(),
            'fecha_vencimiento' => Carbon::now()->endOfYear()->endOfDay(),
            'id_inmueble' => $pagare->id_inmueble,
            'id_moneda' => $pagare->id_moneda,
            'id_persona_acreedora' => $pagare->id_persona_acreedora,
            'id_persona_deudora' => $pagare->id_persona_deudora,
            'id_tabla' => $pagare->id_tabla,
            'monto' => ($pagare->monto - ($pagare->enum_opcion_pago === 'MANUAL' ? $pagare->monto_manual : $pagare->monto_minimo)),
        ])[1];

        $inmueble = $pagarePagoDiferido->idInmueble;
        $inquilinoReferente = $inmueble->idInquilinoReferente->idPersona;
        $targetLabel = $inquilinoReferente->nombre_y_apellidos;
        $targetType = $inquilinoReferente->enum_tipo_persona === 'FISICA' ? 'cip' : 'ruc';
        $targetNumber = $inquilinoReferente->enum_tipo_persona === 'FISICA' ? $inquilinoReferente->ci : $inquilinoReferente->ruc;
        $label = 'Pago diferido para ('.$targetNumber.') '.$targetLabel.', mes '.Carbon::parse($pagare->fecha_pagare)->format('m/Y');

        $datosBoleta = [
            'amount' => [
                'currency' => $pagarePagoDiferido->id_moneda == 1 ? 'PYG' : 'USD',
                'value' => $pagarePagoDiferido->monto,
            ],
            'description' => [
                'summary' => $summary,
                'text' => $summary,
            ],
            'docId' => $pagarePagoDiferido->id,
            'label' => $label,
            'target' => [
                'label' => $targetLabel,
                'number' => $targetNumber,
                'type' => $targetType,
            ],
            'validPeriod' => [
                'end' => Carbon::parse($pagarePagoDiferido->fecha_vencimiento)->toAtomString(),
                'start' => Carbon::parse($pagarePagoDiferido->fecha_pagare)->toAtomString()
            ]
        ];

        $boleta = $this->dispatchNow(new GenerarBoletaPago($datosBoleta));
    }

    protected function actualizarMontoPagare($pagare) {
        $this->repository->update($pagare, ['monto' => ($pagare->enum_opcion_pago === 'MANUAL' ? $pagare->monto_manual : $pagare->monto_minimo)]);
    }
}
