<?php declare(strict_types=1);

namespace App\Infrastructure\Persistence\Sql\Repository;

use App\Infrastructure\Persistence\MySQL\SqlUserRepository;
use App\Infrastructure\Persistence\Sql\Connection\PdoConnectionFactory;

/** SQL implementation of UserRepository port. */
final class UserRepositorySql extends SqlUserRepository
{
    public function __construct(PdoConnectionFactory $connectionFactory)
    {
        parent::__construct($connectionFactory->create());
    }
}
