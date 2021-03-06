<?php

namespace Pim\Bundle\EnrichBundle\Form\Type;

use Doctrine\ORM\EntityRepository;
use Pim\Bundle\CatalogBundle\Helper\LocaleHelper;
use Pim\Bundle\CatalogBundle\Manager\LocaleManager;
use Pim\Bundle\CatalogBundle\Repository\CurrencyRepositoryInterface;
use Pim\Bundle\CatalogBundle\Repository\LocaleRepositoryInterface;
use Pim\Bundle\EnrichBundle\Form\Subscriber\DisableFieldSubscriber;
use Pim\Bundle\EnrichBundle\Helper\SortHelper;
use Pim\Bundle\EnrichBundle\Provider\ColorsProvider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Type for channel form
 *
 * @author    Romain Monceau <romain@akeneo.com>
 * @copyright 2013 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ChannelType extends AbstractType
{
    /** @var LocaleManager */
    protected $localeManager;

    /** @var LocaleHelper */
    protected $localeHelper;

    /** @var ColorsProvider */
    protected $colorsProvider;

    /** @var string */
    protected $categoryClass;

    /** @var string */
    protected $dataClass;

    /**
     * Inject locale manager, locale helper and colors provider in the constructor
     *
     * @param LocaleManager  $localeManager
     * @param LocaleHelper   $localeHelper
     * @param ColorsProvider $provider
     * @param string         $categoryClass
     * @param string         $dataClass
     */
    public function __construct(
        LocaleManager $localeManager,
        LocaleHelper $localeHelper,
        ColorsProvider $provider,
        $categoryClass,
        $dataClass
    ) {
        $this->localeManager = $localeManager;
        $this->localeHelper  = $localeHelper;
        $this->provider      = $provider;
        $this->categoryClass = $categoryClass;
        $this->dataClass     = $dataClass;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this
            ->addCodeField($builder)
            ->addLabelField($builder)
            ->addColorField($builder)
            ->addCurrenciesField($builder)
            ->addLocalesField($builder)
            ->addCategoryField($builder)
            ->addConversionUnitFields($builder)
            ->addEventSubscribers($builder);
    }

    /**
     * Create code field
     *
     * @param FormBuilderInterface $builder
     *
     * @return ChannelType
     */
    protected function addCodeField(FormBuilderInterface $builder)
    {
        $builder->add('code');

        return $this;
    }

    /**
     * Create label field
     *
     * @param FormBuilderInterface $builder
     *
     * @return ChannelType
     */
    protected function addLabelField(FormBuilderInterface $builder)
    {
        $builder->add('label', 'text', ['label' => 'Default label']);

        return $this;
    }

    /**
     * Create color field
     *
     * @param FormBuilderInterface $builder
     *
     * @return ChannelType
     */
    protected function addColorField(FormBuilderInterface $builder)
    {
        $builder->add(
            'color',
            'choice',
            [
                'choices'     => $this->provider->getColorChoices(),
                'select2'     => true,
                'required'    => false,
                'label'       => 'color.title',
                'empty_value' => 'Choose a color'
            ]
        );

        return $this;
    }

    /**
     * Create currencies field
     *
     * @param FormBuilderInterface $builder
     *
     * @return ChannelType
     */
    protected function addCurrenciesField(FormBuilderInterface $builder)
    {
        $builder->add(
            'currencies',
            'entity',
            [
                'required'      => true,
                'multiple'      => true,
                'select2'       => true,
                'class'         => 'Pim\Bundle\CatalogBundle\Entity\Currency',
                'query_builder' => function (CurrencyRepositoryInterface $repository) {
                    return $repository->getActivatedCurrenciesQB();
                }
            ]
        );

        return $this;
    }

    /**
     * Create conversion units field
     *
     * @param FormBuilderInterface $builder
     *
     * @return ChannelType
     */
    protected function addConversionUnitFields(FormBuilderInterface $builder)
    {
        $builder->add('conversionUnits', 'pim_enrich_conversion_units');

        return $this;
    }

    /**
     * Create locales field
     *
     * @param FormBuilderInterface $builder
     *
     * @return ChannelType
     */
    protected function addLocalesField(FormBuilderInterface $builder)
    {
        $builder->add(
            'locales',
            'entity',
            [
                'required'          => true,
                'multiple'          => true,
                'select2'           => true,
                'by_reference'      => false,
                'class'             => 'Pim\Bundle\CatalogBundle\Entity\Locale',
                'query_builder'     => function (LocaleRepositoryInterface $repository) {
                    return $repository->getLocalesQB();
                },
                'preferred_choices' => $this->localeManager->getActiveLocales()
            ]
        );

        return $this;
    }

    /**
     * Create category field
     *
     * @param FormBuilderInterface $builder
     *
     * @return ChannelType
     */
    protected function addCategoryField(FormBuilderInterface $builder)
    {
        $builder->add(
            'category',
            'entity',
            [
                'label'         => 'Category tree',
                'required'      => true,
                'select2'       => true,
                'class'         => $this->categoryClass,
                'query_builder' => function (EntityRepository $repository) {
                    return $repository->getTreesQB();
                }
            ]
        );

        return $this;
    }

    /**
     * Add event subscriber to channel form type
     *
     * @param FormBuilderInterface $builder
     *
     * @return ChannelType
     */
    protected function addEventSubscribers(FormBuilderInterface $builder)
    {
        $builder->addEventSubscriber(new DisableFieldSubscriber('code'));

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * Translate the locale codes to labels in the current user locale
     * and sort them alphabetically
     *
     * This part is done here because of the choices query is executed just before
     * so we can't access to these properties from form events
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        if (!isset($view['locales'])) {
            return;
        }

        /** @var array<ChoiceView> $locales */
        $locales = $view['locales'];
        foreach ($locales->vars['choices'] as $locale) {
            $locale->label = $this->localeHelper->getLocaleLabel($locale->label);
        }
        foreach ($locales->vars['preferred_choices'] as $locale) {
            $locale->label = $this->localeHelper->getLocaleLabel($locale->label);
        }

        $locales->vars['choices'] = SortHelper::sortByProperty($locales->vars['choices'], 'label');
        $locales->vars['preferred_choices'] = SortHelper::sortByProperty(
            $locales->vars['preferred_choices'],
            'label'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => $this->dataClass,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'pim_enrich_channel';
    }
}
