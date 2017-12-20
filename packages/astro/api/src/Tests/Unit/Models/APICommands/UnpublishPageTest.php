<?php
/**
 * Created by PhpStorm.
 * User: sam
 * Date: 27/07/17
 * Time: 11:27
 */

namespace Astro\API\Tests\Unit\Models\APICommands;

use Astro\API\Models\APICommands\UnpublishPage;
use Astro\API\Models\Contracts\APICommand;

class UnpublishPageTest extends APICommandTestCase
{
    public function getValidData()
    {
        return [];
    }

    /**
     * @test
     * @group APICommands
     */
    public function validation_whenInput_isValid_passes()
    {
        $this->markTestIncomplete();
    }

    /**
     * @test
     * @group APICommands
     */
    public function validation_whenPageDoesNotExist_fails()
    {
        $this->markTestIncomplete();
    }

    /**
     * @test
     * @group APICommands
     */
    public function validation_whenPageIsNotDraft_fails()
    {
        $this->markTestIncomplete();
    }

    /**
     * @test
     * @group APICommands
     */
    public function validation_whenPageIsNotPublished_fails()
    {
        $this->markTestIncomplete();
    }

    /**
     * @return APICommand A new instance of the class to test.
     */
    public function command()
    {
        return new UnpublishPage();
    }
}