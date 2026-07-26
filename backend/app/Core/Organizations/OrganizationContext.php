<?php

declare(strict_types=1);

namespace App\Core\Organizations;

use Illuminate\Http\Request;
use LogicException;

final class OrganizationContext
{
    private const ATTRIBUTE = 'organization_id';

    public function __construct(
        private readonly Request $request,
    ) {}

    public function set(int $organizationId): void
    {
        if ($organizationId < 1) {
            throw new LogicException(
                'Organization ID must be positive.',
            );
        }

        $this->request->attributes->set(
            self::ATTRIBUTE,
            $organizationId,
        );
    }

    public function id(): ?int
    {
        $value = $this->request->attributes->get(
            self::ATTRIBUTE,
        );

        return is_int($value) && $value > 0
            ? $value
            : null;
    }

    public function requireId(): int
    {
        return $this->id()
            ?? throw new LogicException(
                'Verified organization context is not available.',
            );
    }

    public function clear(): void
    {
        $this->request->attributes->remove(
            self::ATTRIBUTE,
        );
    }
}
