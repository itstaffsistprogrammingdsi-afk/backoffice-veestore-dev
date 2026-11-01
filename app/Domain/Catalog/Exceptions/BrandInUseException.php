<?php

namespace App\Domain\Catalog\Exceptions;

use RuntimeException;

class BrandInUseException extends RuntimeException
{
    public function __construct(
        public readonly string $brandName,
        public readonly int $usedCount,
    ) {
        parent::__construct(sprintf(
            'Brand "%s" tidak dapat dihapus karena masih digunakan oleh %d produk.',
            $brandName,
            $usedCount
        ));
    }
}
