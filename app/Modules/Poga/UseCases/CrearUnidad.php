<?php

namespace Raffles\Modules\Poga\UseCases;

use Raffles\Modules\Poga\Models\{ Inmueble, Unidad, User };
use Raffles\Modules\Poga\Repositories\{ InmuebleRepository, PersonaRepository, UnidadRepository };
use Raffles\Modules\Poga\Notifications\{ UnidadCreada, UnidadAsignada };

use Illuminate\Foundation\Bus\DispatchesJobs;

class CrearUnidad
{
    use DispatchesJobs;

    /**
     * The form data and the User model.
     *
     * @var array
     * @var User
     */
    protected $data, $user;

    /**
     * Create a new job instance.
     *
     * @param array $data The form data.
     * @param User  $user The User model.
     *
     * @return void
     */
    public function __construct($data, User $user)
    {
        $this->data = $data;
        $this->user = $user;
    }

    /**
     * Execute the job.
     *
     * @param InmuebleRepository $rInmueble The InmuebleRepository object.
     * @param UnidadRepository   $rUnidad   The UnidadRepository object.
     * @param PersonaRepository  $rPersona  The PersonaRepository object.
     *
     * @return void
     */
    public function handle(InmuebleRepository $rInmueble, UnidadRepository $rUnidad, PersonaRepository $rPersona)
    {
        $inmueble = $this->crearInmueble($rInmueble);

        $this->adjuntarCaracteristicas($inmueble);

        $unidad = $this->crearUnidad($rUnidad, $inmueble);

        $this->nominarOAsignarAdministrador($rPersona, $unidad);
        $this->nominarOAsignarPropietario($rPersona, $unidad);

        $this->user->notify(new UnidadCreada($unidad, $this->user));

        return $unidad;
    }


    /**
     * Adjuntar Características.
     *
     * @param Inmueble $inmueble The Inmueble model.
     *
     * @return void
     */
    protected function adjuntarCaracteristicas($inmueble)
    {
        $caracteristicasTipoInmueble = $this->data['caracteristicas'];
        foreach ($caracteristicasTipoInmueble as $caracteristicaTipoInmueble) {
                $inmueble->caracteristicas()->attach($caracteristicaTipoInmueble['id_caracteristica']['id'], ['cantidad' => $caracteristicaTipoInmueble['id_caracteristica']['cantidad'], 'enum_estado' => 'ACTIVO', 'id_caracteristica_tipo_inmueble' => $caracteristicaTipoInmueble['id']]);
        }
    }

    /**
     * Crear Inmueble.
     *
     * @param InmuebleRepository $repository The InmuebleRepository object.
     *
     * @return Inmueble 
     */
    protected function crearInmueble(InmuebleRepository $repository)
    {
        return $repository->create(
            array_merge(
                $this->data['id_inmueble'],
                [
                'enum_estado' => 'ACTIVO',
                'enum_tabla_hija' => 'UNIDADES',
                'id_usuario_creador' => $this->user->id,
                ]
            )
        )[1];
    }

    /**
     * Crear Unidad.
     *
     * @param UnidadRepository $repository The UnidadRepository object.
     * @param Inmueble         $inmueble   The Inmueble model.
     *
     * @return Unidad
     */
    protected function crearUnidad(UnidadRepository $repository, Inmueble $inmueble)
    {
        $unidad = $repository->create(
            [
            'area' => $this->data['area'],
            'area_estacionamiento' => $this->data['area_estacionamiento'],
                'id_formato_inmueble' => $this->data['id_formato_inmueble'],
            'id_inmueble' => $inmueble->id,
            'id_inmueble_padre' => $this->data['id_inmueble_padre'],
            'id_medida' => $this->data['id_medida'],
            'numero' => $this->data['numero'],
            'piso' => $this->data['piso']
            ]
        )[1];

        $inmueble->id_tabla_hija = $unidad->id;
        $inmueble->save();

        return $unidad;
    }

    /**
     * Nominar o Asignar Administrador Referente.
     *
     * @param PersonaRepository $repository The PersonaRepository object.
     * @param Unidad            $unidad     The Unidad model.
     *
     * @return void
     */
    protected function nominarOAsignarAdministrador(PersonaRepository $repository, Unidad $unidad)
    {
        // idAdministradorReferente presente en el array?
        if (isset($this->data['id_administrador_referente'])) {
            $user = $this->user;

            $id = $this->data['id_administrador_referente'];

            // idAdministradorReferente no está vacío?
            if ($id) {
                // idAdministradorReferente es distinto al id de la persona del usuario?
                if ($id != $user->id_persona) {
                    $persona = $repository->findOrFail($id);

                    $this->dispatch(new NominarAdministradorReferenteParaUnidad($persona, $unidad, $user));
                } else {
                           $persona = $user->idPersona;
                    $this->dispatch(new RelacionarAdministradorReferente($persona, $unidad->idInmueble));
                }
            }
        }
    }

    /**
     * Nominar O Asignar Propietario Referente.
     *
     * @param PersonaRepository $repository The PersonaRepository object.
     * @param Unidad            $unidad     The Unidad model.
     *
     * @return void
     */
    protected function nominarOAsignarPropietario(PersonaRepository $repository, Unidad $unidad)
    {
        // idPropietarioReferente no está presente en el array?
        if (isset($this->data['id_propietario_referente'])) {
            $user = $this->user;

            $id = $this->data['id_propietario_referente'];

            // idPropietarioReferente no está vacío?
            if ($id) {
                // idPropietarioReferente es distinto al id de la persona del usuario?
                if ($id != $user->id_persona) {
                    $persona = $repository->findOrFail($id);

                    // Sólo si la modalidad del inmueble padre es en condominio.
                    if ($unidad->idInmueblePadre->modalidad_propiedad === 'EN_CONDOMNIO') {
                        $this->dispatch(new NominarPropietarioReferenteParaUnidad($persona, $unidad, $user));
                    } else {
                        $this->dispatch(new RelacionarPropietarioReferente($persona, $unidad->idInmueble));
                        $persona->user->notify(new UnidadAsignada($unidad));
                    }
                } else {
                    $persona = $user->idPersona;
                    $this->dispatch(new RelacionarPropietarioReferente($persona, $unidad->idInmueble));
                }
            }
        }
    }
}
