<?php

declare(strict_types=1);

namespace Tests\Unit;

use ModulesShoppingComplex\Shared\Support\DistanceLabel;
use PHPUnit\Framework\TestCase;

class DistanceLabelTest extends TestCase
{
    public function test_a_gps_grade_fix_keeps_the_decimal(): void
    {
        $this->assertSame('3.4 km away', DistanceLabel::format(3.42, 25.0));
        $this->assertSame('450m away', DistanceLabel::format(0.45, 25.0));
    }

    public function test_a_whole_number_of_kilometres_keeps_its_decimal(): void
    {
        $this->assertSame('2.0 km away', DistanceLabel::format(2.0, 25.0));
    }

    public function test_a_distance_rounding_up_to_a_kilometre_is_not_quoted_in_metres(): void
    {
        $this->assertSame('1.0 km away', DistanceLabel::format(0.9996, 25.0));
    }

    public function test_a_source_reporting_no_accuracy_is_trusted_exactly(): void
    {
        $this->assertSame('7.4 km away', DistanceLabel::format(7.41));
    }

    public function test_a_wifi_grade_fix_loses_the_decimal_it_never_measured(): void
    {
        $this->assertSame('about 3 km away', DistanceLabel::format(3.42, 4000.0));
    }

    public function test_a_sub_kilometre_distance_is_never_quoted_in_metres_off_a_coarse_fix(): void
    {
        $this->assertSame('about 1 km away', DistanceLabel::format(0.45, 4000.0));
    }

    public function test_a_fix_too_coarse_to_support_any_claim_yields_no_label(): void
    {
        $this->assertNull(DistanceLabel::format(3.42, 25000.0));
    }

    public function test_the_thresholds_are_inclusive_at_their_boundaries(): void
    {
        $this->assertSame('3.4 km away', DistanceLabel::format(3.42, (float) DistanceLabel::PRECISE_METERS));
        $this->assertSame('about 3 km away', DistanceLabel::format(3.42, (float) DistanceLabel::USABLE_METERS));
    }
}
