<?php

declare(strict_types=1);

namespace Neko\Framework\Dotenv\Loader;

use Neko\Framework\Dotenv\Repository\RepositoryInterface;

interface LoaderInterface
{
    /**
     * Load the given entries into the repository.
     *
     * @param \Neko\Framework\Dotenv\Repository\RepositoryInterface $repository
     * @param \Neko\Framework\Dotenv\Parser\Entry[]                 $entries
     *
     * @return array<string,string|null>
     */
    public function load(RepositoryInterface $repository, array $entries);
}
