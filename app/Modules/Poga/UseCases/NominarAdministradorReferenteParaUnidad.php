<?php

namespace Raffles\Modules\Poga\UseCases;

use Raffles\Modules\Poga\Models\{ Persona, Unidad, User };
use Raffles\Modules\Poga\Repositories\NominacionRepository;
use Raffles\Modules\Poga\Notifications\PersonaNominadaParaUnidad;

use Illuminate\Foundation\Bus\Dispatchable;

class NominarAdministradorReferenteParaUnidad
{
    use Dispatchable;

    /**
     * The Persona, Unidad and User models.
     *
     * @var Persona  $persona  The Persona model.
     * @var Unidad   $unidad   The Unidad model.
     */
    protected $persona, $unidad, $user;

    /**
     * Create a new job instance.
     *
     * @param Persona $persona The Persona model.
     * @param Unidad  $unidad  The Unidad model.
     * @param User    $user    The User model.
     *
     * @return void
     */
    public function __construct(Persona $persona, Unidad $unidad, User $user)
    {
        $this->persona = $persona;
        $this->unidad = $unidad;
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
        'enum_estado' => 'PENDIENTE',
            'fecha_hora' => \Carbon\Carbon::now(),
            'id_inmueble' => $this->unidad->id_inmueble,
            'id_persona_nominada' => $this->persona->id,
            'id_usuario_principal' => $this->user->id,
            'referente' => '1',
            'role_id' => '1',
            'usu_alta' => $this->user->id,
        ];

        $nominacion = $repository->updateOrCreate(
            ['enum_estado' => 'PENDIENTE', 'id_inmueble' => $this->unidad->id_inmueble, 'id_persona_nominada' => $this->persona->id, 'role_id' => '1'],
            $data
        );
        //$nominacion = $repository->create($data)[1];

        $personaNominada = $nominacion->idPersonaNominada;
        $usuario = $personaNominada->user;

        if ($usuario) {
            $usuario->notify(new PersonaNominadaParaUnidad($personaNominada, $nominacion, $this->unidad));
        }

        return $nominacion;
    }
}
