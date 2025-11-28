<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     title="CHELSY Restaurant API",
 *     version="1.0.0",
 *     description="API REST pour l'application mobile CHELSY Restaurant",
 *     @OA\Contact(
 *         email="contact@chelsy-restaurant.bj"
 *     )
 * )
 * 
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="Serveur API"
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Authentification par token Sanctum"
 * )
 */
abstract class Controller
{
    //
}
