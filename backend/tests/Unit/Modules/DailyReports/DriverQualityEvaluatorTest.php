<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\DailyReports;

use App\Modules\DailyReports\Models\DriverQualityProfileComponent;
use App\Modules\DailyReports\Models\DriverQualityProfileVersion;
use App\Modules\DailyReports\Services\DriverQualityEvaluator;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\TestCase;

final class DriverQualityEvaluatorTest extends TestCase
{
    public function test_only_selected_raw_components_form_the_quality_numerator(): void
    {
        $version = $this->version([
            DriverQualityProfileComponent::SOURCE_DELIVERED,
            DriverQualityProfileComponent::SOURCE_REDIRECTED,
        ]);

        $result = (new DriverQualityEvaluator)->evaluate(
            $version,
            [
                'loaded_parcels' => 100,
                'delivered_parcels' => 70,
                'redirected_parcels' => 20,
                'customer_rejected_parcels' => 5,
            ],
        );

        self::assertTrue($result['applied']);
        self::assertTrue($result['complete']);
        self::assertSame('evaluated', $result['reason']);
        self::assertSame(90, $result['numerator_parcels']);
        self::assertSame(100, $result['denominator_parcels']);
        self::assertSame(90.0, $result['value_percent']);
    }

    public function test_disabled_profile_explicitly_suppresses_quality(): void
    {
        $version = new DriverQualityProfileVersion([
            'calculation_method' => DriverQualityProfileVersion::METHOD_DISABLED,
        ]);

        $result = (new DriverQualityEvaluator)->evaluate(
            $version,
            ['loaded_parcels' => 100],
        );

        self::assertFalse($result['applied']);
        self::assertTrue($result['complete']);
        self::assertSame('disabled', $result['reason']);
        self::assertNull($result['value_percent']);
    }

    public function test_missing_or_inconsistent_source_data_is_not_financially_usable(): void
    {
        $version = $this->version(
            DriverQualityProfileComponent::SOURCES,
        );

        $missing = (new DriverQualityEvaluator)->evaluate(
            $version,
            [
                'loaded_parcels' => 100,
                'delivered_parcels' => 70,
                'redirected_parcels' => null,
                'customer_rejected_parcels' => 5,
            ],
        );

        self::assertFalse($missing['applied']);
        self::assertFalse($missing['complete']);
        self::assertSame('incomplete_data', $missing['reason']);

        $inconsistent = (new DriverQualityEvaluator)->evaluate(
            $version,
            [
                'loaded_parcels' => 100,
                'delivered_parcels' => 80,
                'redirected_parcels' => 20,
                'customer_rejected_parcels' => 1,
            ],
        );

        self::assertFalse($inconsistent['applied']);
        self::assertFalse($inconsistent['complete']);
        self::assertSame('inconsistent_data', $inconsistent['reason']);
        self::assertNull($inconsistent['value_percent']);
    }

    /**
     * @param  list<string>  $sources
     */
    private function version(array $sources): DriverQualityProfileVersion
    {
        $version = new DriverQualityProfileVersion([
            'calculation_method' => DriverQualityProfileVersion::METHOD_PROCESSED_SHARE,
        ]);

        $components = [];

        foreach ($sources as $index => $source) {
            $components[] = new DriverQualityProfileComponent([
                'source_code' => $source,
                'position' => $index + 1,
            ]);
        }

        $version->setRelation(
            'components',
            new Collection($components),
        );

        return $version;
    }
}
