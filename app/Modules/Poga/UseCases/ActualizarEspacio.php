<?php

namespace Raffles\Modules\Poga\UseCases;

use Raffles\Modules\Poga\Repositories\EspacioRepository;
use Raffles\Modules\Poga\Notifications\EspacioActualizada;

use Illuminate\Foundation\Bus\DispatchesJobs;

class ActualizarEspacio
{
    use DispatchesJobs;

    /**
     * The form data, the Espacio id, and the User model.
     *
     * @var int   $id
     * @var array $data
     * @var User  $user
     */
    protected $id, $data, $user;

    /**
     * Create a new job instance.
     *
     * @param int   $id   The Espacio id.
     * @param array $data The form data.
     * @param User  $user The User model.
     *
     * @return void
     */
    public function __construct($id, $data, $user)
    {
        $this->id = $id;
        $this->data = $data;
        $this->user = $user;
    }

    /**
     * Execute the job.
     *
     * @param EspacioRepository $repository The EspacioRepository object.
     *
     * @return void
     */
    public function handle(EspacioRepository $repository)
    {
        return $repository->update($this->id, $this->data)[1];
    }
}
