<?php

namespace Raffles\Modules\Poga\Http\Controllers\Cuenta;

use Raffles\Http\Controllers\Controller;
use Raffles\Modules\Poga\Http\Requests\CuentaRequest;

use RafflesArgentina\ResourceController\Traits\FormatsValidJsonResponses;

class ActualizarCuentaController extends Controller
{
    use FormatsValidJsonResponses;

    /**
     * Handle the incoming request.
     *
     * @param  CuentaRequest $request The FormRequest object.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(CuentaRequest $request)
    {
        $user = $request->user('api');
        $user->update(array_except($request->all(), ['id_persona']));
        $user->idPersona()->update($request->id_persona);

        return $this->validSuccessJsonResponse('Success', $user);
    }
}
