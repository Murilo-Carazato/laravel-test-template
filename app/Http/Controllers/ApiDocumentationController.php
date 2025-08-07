<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * @OA\Info(
 *     title="API do Template Flutter/Laravel",
 *     version="1.0.0",
 *     description="Documentação da API do template Flutter/Laravel",
 *     @OA\Contact(
 *         email="contato@exemplo.com.br",
 *         name="Equipe de Desenvolvimento"
 *     )
 * )
 * @OA\Server(
 *     description="Ambiente de Desenvolvimento",
 *     url=L5_SWAGGER_CONST_HOST
 * )
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 */
class ApiDocumentationController extends Controller
{
    /**
     * Exibe a documentação da API
     */
    public function index()
    {
        return view('l5-swagger.index');
    }
}