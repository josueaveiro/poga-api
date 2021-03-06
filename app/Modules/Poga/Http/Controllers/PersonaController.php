<?php

namespace Raffles\Modules\Poga\Http\Controllers;

use Raffles\Modules\Poga\Http\Requests\PersonaRequest;
use Raffles\Modules\Poga\Repositories\PersonaRepository;
use Raffles\Modules\Poga\UseCases\CrearPersona;

use Illuminate\Http\Request;
use RafflesArgentina\ResourceController\Traits\FormatsValidJsonResponses;

class PersonaController extends Controller
{
    use FormatsValidJsonResponses;

    /**
     * The PersonaRepository object.
     *
     * @var PersonaRepository $persona
     */
    protected $repository;

    /**
     * Create a new PersonaController instance.
     *
     * @param PersonaRepository $repository The PersonaRepository object.
     *
     * @return void
     */
    public function __construct(PersonaRepository $repository)
    {
        $this->middleware('auth:api');

        $this->repository = $repository;
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $items = $this->repository->findPersonas();

        return $this->validSuccessJsonResponse('Success', $items);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  PersonaRequest $request The FormRequest object.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(PersonaRequest $request)
    {
        $data = $request->all();
        $user = $request->user('api');
        $inmueblePersona = $this->dispatchNow(new CrearPersona($data, $user));

        return $this->validSuccessJsonResponse('Success', $inmueblePersona);
    }

    /**
     * Display the specified resource.
     *
     * @param Request $request
     * @param int     $id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param int     $id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Request $request
     * @param int     $id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $id)
    {
        //
    }
}
