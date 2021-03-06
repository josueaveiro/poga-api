<?php

namespace Raffles\Modules\Poga\Http\Controllers\Auth;

use Raffles\Modules\Poga\Models\User;
use Raffles\Modules\Poga\Http\Controllers\Controller;
use Raffles\Modules\Poga\UseCases\RegistroUsuarioInvitado;

use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use RafflesArgentina\ResourceController\Traits\FormatsValidJsonResponses;

class GuestRegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use FormatsValidJsonResponses, RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest:api');
    }

    /**
     * Handle a guest registration request for the application.
     *
     * @param \Illuminate\Http\Request $request
     * @param string                   $codigo_validacion
     *
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request, $codigo_validacion)
    {
        $user = User::where('codigo_validacion', $codigo_validacion)->firstOrFail();

        $this->validator($request->all(), $user)->validate();

        $data = $request->all();

        $this->dispatchNow(new RegistroUsuarioInvitado($data, $user));

        return $user;

    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data, User $user)
    {
        return Validator::make(
            $data, [
	    'accepted' => 'required|accepted',
	    'email' => 'required|email',
            'id_persona.apellido' => 'required_if:enum_tipo_persona,FISICA',
            //'id_persona.ciudades_cobertura' => 'sometimes|array',
            'id_persona.fecha_nacimiento' => 'nullable|date',
            //'id_persona.id_pais' => 'sometimes|required',
            //'id_persona.id_pais_cobertura' => 'sometimes|required',
            'id_persona.nombre' => 'required',
            //'id_persona.ci' => 'sometimes|required',
            'password' => 'required|confirmed',
            //'plan' => [
                //Rule::requiredIf(
                    //function () use ($user) {
                        //return $user->role_id === '1';
                    //}
                //)
            //]
            ]
        );
    }
}
