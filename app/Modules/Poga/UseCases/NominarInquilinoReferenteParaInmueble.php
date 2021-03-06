<?php

namespace Raffles\Modules\Poga\UseCases;

use Raffles\Modules\Poga\Models\{ Inmueble, Persona, User };
use Raffles\Modules\Poga\Repositories\NominacionRepository;
use Raffles\Modules\Poga\Notifications\PersonaNominadaParaInmueble;

use Illuminate\Foundation\Bus\Dispatchable;

class NominarInquilinoReferenteParaInmueble
{
    use Dispatchable;

    /**
     * The Persona and Inmueble models.
     *
     * @var Persona  $persona  The Persona model.
     * @var Inmueble $inmueble The Inmueble model.
     * @var User     $user     The User model.
     */
    protected $persona, $inmueble, $user;

    /**
     * Create a new job instance.
     *
     * @param Persona  $persona  The Persona model.
     * @param Inmueble $inmueble The Inmueble model.
     * @param User     $user     The User model.
     *
     * @return void
     */
    public function __construct(Persona $persona, Inmueble $inmueble, User $user)
    {
        $this->persona = $persona;
        $this->inmueble = $inmueble;
        $this->user = $user;
    }

    /**
     * Execute the job.
     *
     * @param NominacionRepository $repository The NominacionRepository object.
     *
     * @return void
     */
    public function handle(NominacionRepository $repository)
    {
        $data = [
	    //'enum_estado' => 'PENDIENTE', // Debería ser pendiente
	    'enum_estado' => 'ACEPTADO',
            'fecha_hora' => \Carbon\Carbon::now(),    
            'id_inmueble' => $this->inmueble->id,
            'id_persona_nominada' => $this->persona->id,
            'id_usuario_principal' => $this->user->id,
            'referente' => '1',
            'role_id' => '3',
            'usu_alta' => $this->user->id,
        ];

        $nominacion = $repository->updateOrCreate(
	    //['enum_estado' => 'PENDIENTE', 'id_inmueble' => $this->inmueble->id, 'id_persona_nominada' => $this->persona->id, 'role_id' => '3'], // Debería ser pendiente
	    ['enum_estado' => 'ACEPTADO', 'id_inmueble' => $this->inmueble->id, 'id_persona_nominada' => $this->persona->id, 'role_id' => '3'],
		//
            $data
        );

	// Esta línea tiene que ser eliminada.
	$inmueblePersona = \Raffles\Modules\Poga\Models\InmueblePersona::create(['id_persona' => $nominacion->id_persona_nominada, 'id_inmueble' => $nominacion->id_inmueble, 'referente' => '1', 'enum_rol' => 'INQUILINO']);

        $personaNominada = $nominacion->idPersonaNominada;
        $user = $personaNominada->user;

        if ($user) {
            // Adjunta el rol inquilino.
            $user->roles()->syncWithoutDetaching(3);
            //$user->notify(new PersonaNominadaParaInmueble($personaNominada, $nominacion, $this->inmueble));
        }

        return $nominacion;
    }
}
