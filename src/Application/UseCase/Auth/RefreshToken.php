<?php declare(strict_types=1);

namespace App\Auth;

/**
 * Use case: Exchange refresh token for new access token via JwtEncoderInterface.
 *
 * Notes:
 *  - No HTTP/SQL here. Coordinates Domain + Ports only.
 *  - Validate input DTOs; return output DTOs.
 */
final class RefreshToken {}
