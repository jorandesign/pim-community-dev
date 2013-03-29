<?php
namespace Pim\Bundle\ProductBundle\Entity;

use Gedmo\Translatable\Translatable;

use Gedmo\Mapping\Annotation as Gedmo;

use Doctrine\ORM\Mapping as ORM;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\SegmentationTreeBundle\Entity\AbstractSegment;

/**
 * Segment class allowing to organize a flexible product class into trees
 *
 * @author    Romain Monceau <romain@akeneo.com>
 * @copyright 2012 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 * @ORM\Entity(repositoryClass="Oro\Bundle\SegmentationTreeBundle\Entity\Repository\SegmentRepository")
 * @ORM\Table(name="pim_product_segment")
 * @Gedmo\Tree(type="nested")
 * @Gedmo\TranslationEntity(class="Pim\Bundle\ProductBundle\Entity\ProductSegmentTranslation")
 */
class ProductSegment extends AbstractSegment implements Translatable
{
    /**
     * @var ProductSegment $parent
     *
     * @Gedmo\TreeParent
     * @ORM\ManyToOne(targetEntity="ProductSegment", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $parent;

    /**
     * @var \Doctrine\Common\Collections\Collection $children
     *
     * @ORM\OneToMany(targetEntity="ProductSegment", mappedBy="parent", cascade={"persist"})
     * @ORM\OrderBy({"left" = "ASC"})
     */
    protected $children;

    /**
     * @var \Doctrine\Common\Collections\Collection $products
     *
     * @ORM\ManyToMany(targetEntity="Product")
     * @ORM\JoinTable(
     *     name="pim_segments_products",
     *     joinColumn={@ORM\JoinColumn(name="segment_id", referencedColumnName="id")}
     *     inverseJoinColumns={@ORM\JoinColumn(name="product_id", referencedColumnName="id")}
     * )
     */
    protected $products;

    /**
     * @var string $title
     *
     * @Gedmo\Translatable
     */
    protected $title;

    /**
     * Used locale to override Translation listener`s locale
     * this is not a mapped field of entity metadata, just a simple property
     *
     * @var string $locale
     *
     * @Gedmo\Locale
     */
    protected $locale;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->products = new ArrayCollection();
    }

    /**
     * Add product to this segment node
     *
     * @param Product $product
     *
     * @return \Pim\Bundle\ProductBundle\Entity\ProductSegment
     */
    public function addProduct(Product $product)
    {
        $this->products[] = $product;

        return $this;
    }

    /**
     * Remove product for this segment node
     *
     * @param Product $product
     *
     * @return \Pim\Bundle\ProductBundle\Entity\ProductSegment
     */
    public function removeProduct(Product $product)
    {
        $this->products->removeElement($product);

        return $this;
    }

    /**
     * Get products for this segment node
     *
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getProducts()
    {
        return $this->products;
    }

    /**
     * Define locale used by entity
     *
     * @param string $locale
     *
     * @return AbstractSegment
     */
    public function setTranslatableLocale($locale)
    {
        $this->locale = $locale;

        return $this;
    }
}
