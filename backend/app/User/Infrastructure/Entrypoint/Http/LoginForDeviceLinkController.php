<?php

namespace App\User\Infrastructure\Entrypoint\Http;

use App\User\Application\AuthenticateForDeviceLink\AuthenticateForDeviceLink;
use App\User\Domain\Exception\InvalidCredentialsException;
use App\User\Domain\Exception\OnlyAdminsCanLinkDeviceException;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Infrastructure\Entrypoint\Http\Requests\LoginForDeviceLinkRequest;
use Illuminate\Http\JsonResponse;

final class LoginForDeviceLinkController
{
    public function __construct(
        private AuthenticateForDeviceLink $authenticateForDeviceLink,
    ) {}

    public function __invoke(LoginForDeviceLinkRequest $request): JsonResponse
    {
        try {
            $response = ($this->authenticateForDeviceLink)($request->toCommand());
        } catch (UserNotFoundException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 404);
        } catch (InvalidCredentialsException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 401);
        } catch (OnlyAdminsCanLinkDeviceException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 403);
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        $request->session()->regenerate();
        $request->session()->put('auth_user_id', $response->id);

        return new JsonResponse($response->toArray(), 200);
    }
}
