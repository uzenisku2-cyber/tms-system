<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Exceptions;

use DomainException;

final class InvalidConditionalRewardConfiguration extends DomainException
{
    /** @param list<string> $rewardQuantitySources */
    public static function incompatible(
        string $rewardMethod,
        array $rewardQuantitySources,
        ?string $rewardTargetItemCode,
    ): self {
        return new self(
            sprintf(
                'Reward method [%s] is incompatible with %d quantity sources and target item [%s].',
                $rewardMethod,
                count($rewardQuantitySources),
                $rewardTargetItemCode ?? 'null',
            ),
        );
    }
}
