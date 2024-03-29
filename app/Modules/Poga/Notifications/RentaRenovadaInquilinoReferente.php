<?php

namespace Raffles\Modules\Poga\Notifications;

use Raffles\Modules\Poga\Models\Renta;

use Gr8Shivam\SmsApi\Notifications\SmsApiChannel;
use Gr8Shivam\SmsApi\Notifications\SmsApiMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class RentaRenovadaInquilinoReferente extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The Renta model.
     *
     * @var Renta
     */
    protected $renta;

    /**
     * Create a new notification instance.
     *
     * @param Renta $renta The Renta model.
     *
     * @return void
     */
    public function __construct(Renta $renta)
    {
        $this->renta = $renta;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     *
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail', SmsApiChannel::class];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     *
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        try {
	    $renta = $this->renta;
	    $inmueble = $renta->idInmueble;
	    $unidad = $renta->idUnidad;

	    $line2 = null;
	    $line3 = null;
	    $line4 = null;
	    $line5 = null;
	
	    switch ($renta->renovacion) {
            case 'AUTOMATICA':
                $accion = 'se renovará por un año a partir del '.$renta->fecha_fin->addDay()->format('d/m/Y').' con las mismas condiciones del contrato vigente.';

	    break;
            case 'MANUAL':
	        $accion = 'se renovará con las siguientes condiciones:';
                $line2 = 'Fecha inicio: '.$renta->fecha_inicio->format('d/m/Y');
                $line3 = 'Fecha de finalización: '.$renta->fecha_fin->format('d/m/Y');
                $line4 = 'Monto mensual de renta: '.number_format($renta->monto,0,',','.').' '.$renta->idMoneda->abbr;
                $line5 = 'Propietario: '.$inmueble->idPropietarioReferente->idPersona->nombre_y_apellidos;
	    break;
            case 'NO_RENOVAR':
                $accion = 'finalizará el '.$renta->fecha_fin->format('d/m/Y').' y no será renovado.';
	    break;
	    }

	    if ($unidad) {
	        $direccion = $unidad->idInmueblePadre->idDireccion;
	        $line = 'El contrato de renta para el '.$unidad->tipo.' '.' piso '.$unidad->piso.' nº '.$unidad->numero.' del inmueble "'.$unidad->idInmueblePadre->nombre.'", ubicado en '.$direccion->calle_principal.' '.($direccion->numeracion ? $direccion->numeracion : ($direccion->calle_secundaria ? 'c/ '.$direccion->numeracion : '')).', '.$accion;
	    } else {
                $direccion = $inmueble->idInmueblePadre->idDireccion;
                $line = 'El contrato de renta para el inmueble "'.$inmueble->idInmueblePadre->nombre.'", ubicado en '.$direccion->calle_principal.' '.($direccion->numeracion ? $direccion->numeracion : ($direccion->calle_secundaria ? 'c/ '.$direccion->numeracion : '')).', '.$accion;
	    }

            return (new MailMessage)
                ->subject('Contrato de renta renovado')
                ->greeting('Hola '.$notifiable->idPersona->nombre)
	        ->line($line)
	        ->line($line2)
	        ->line($line3)
	        ->line($line4)
	        ->line($line5)
		->action('Ir a "Mis Contratos"', str_replace('api.', 'app.', url('/cuenta/mis-rentas')));
        } catch(\Exception $e) {

        }
    }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     *
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }

    public function toSmsApi($notifiable)
    {
	$renta = $this->renta;
        $inmueble = $renta->idInmueble;
	$unidad = $renta->idUnidad;

        switch ($renta->renovacion) {
        case 'AUTOMATICA':
            $accion = 'se renovará con las mismas condiciones';

        break;
        case 'MANUAL':
            $accion = 'se renovará';
        break;
        case 'NO_RENOVAR':
            $accion = 'no será renovado.';
        break;
	}

	if ($unidad) {
		$content = normalize('Tu contrato de renta para el '.$unidad->tipo.' nro '.$unidad->numero.' '.$accion.'. Ver detalles en: '.str_replace('api.', 'app.', url('/cuenta/mis-rentas')));
            return (new SmsApiMessage)
                ->content($content);
	} else {
		$content = normalize('Tu contrato de renta para el inmueble '.str_limit($inmueble->idInmueblePadre->nombre,17).' '.$accion.'. Ver detalles en: '.str_replace('api.', 'app.', url('/cuenta/mis-rentas')));
            return (new SmsApiMessage)
                ->content($content);
	}
    }
}
