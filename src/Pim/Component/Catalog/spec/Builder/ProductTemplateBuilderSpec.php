<?php

namespace spec\Pim\Component\Catalog\Builder;

use PhpSpec\ObjectBehavior;
use Pim\Component\Catalog\Builder\ProductBuilderInterface;
use Pim\Component\Catalog\Model\AttributeInterface;
use Pim\Component\Catalog\Model\ProductInterface;
use Pim\Component\Catalog\Model\ProductTemplateInterface;
use Pim\Component\Catalog\Model\ProductValueCollectionInterface;
use Pim\Component\Catalog\Model\ProductValueInterface;

class ProductTemplateBuilderSpec extends ObjectBehavior
{
    function let(ProductBuilderInterface $productBuilder)
    {
        $this->beConstructedWith(
            $productBuilder,
            'Pim\Bundle\CatalogBundle\Entity\ProductTemplate'
        );
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Pim\Component\Catalog\Builder\ProductTemplateBuilder');
    }

    function it_creates_product_templates()
    {
        $this->createProductTemplate()->shouldReturnAnInstanceOf('Pim\Bundle\CatalogBundle\Entity\ProductTemplate');
    }

    function it_adds_attributes_to_a_product_template(
        $productBuilder,
        ProductTemplateInterface $template,
        AttributeInterface $name,
        ProductValueCollectionInterface $originalValues,
        ProductValueCollectionInterface $newValues,
        ProductInterface $product
    ) {
        $productBuilder->createProduct()->willReturn($product);

        $template->getValues()->willReturn($originalValues);
        $product->setValues($originalValues)->shouldBeCalled();

        $productBuilder->addAttribute($product, $name)->shouldBeCalled();
        $productBuilder->addMissingProductValues($product)->shouldBeCalled();

        $product->getValues()->willReturn($newValues);

        $template->setValues($newValues)->shouldBeCalled();

        $this->addAttributes($template, [$name]);
    }

    function it_removes_attributes_from_a_product_template(
        ProductTemplateInterface $template,
        ProductValueInterface $nameValue,
        ProductValueInterface $colorValue,
        AttributeInterface $name,
        AttributeInterface $color,
        ProductValueCollectionInterface $values
    ) {
        $nameValue->getAttribute()->willReturn($name);
        $colorValue->getAttribute()->willReturn($color);

        $template->getValues()->willReturn($values);

        $values->removeByAttribute($name)->shouldBeCalled();

        $this->removeAttribute($template, $name);
    }
}
