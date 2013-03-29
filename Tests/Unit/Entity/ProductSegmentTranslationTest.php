<?php
namespace Pim\Bundle\ProductBundle\Tests\Unit\Entity;

use Pim\Bundle\ProductBundle\Entity\ProductSegmentTranslation;

/**
 * Test related class
 *
 * @author    Romain Monceau <romain@akeneo.com>
 * @copyright 2012 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 */
class ProductSegmentTranslationTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Test related method
     */
    public function testConstruct()
    {
        $segment = new ProductSegmentTranslation();

        $this->assertInstanceOf('\Gedmo\Translatable\Entity\MappedSuperclass\AbstractTranslation', $segment);
    }
}