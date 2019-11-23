<?php
namespace Povs\ListerBundle\Service;

use Povs\ListerBundle\DependencyInjection\Locator\SelectorTypeLocator;
use Povs\ListerBundle\Exception\ListException;
use Povs\ListerBundle\Mapper\ListField;
use Povs\ListerBundle\View\FieldView;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @author Povilas Margaiatis <p.margaitis@gmail.com>
 */
class ValueAccessor
{
    private const ARRAY_TO_STRING_DELIMITER = ' ';

    /**
     * @var ConfigurationResolver
     */
    private $configuration;

    /**
     * @var ListTypeResolver
     */
    private $typeResolver;

    /**
     * @var SelectorTypeLocator
     */
    private $selectorTypeLocator;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * ValueAccessor constructor.
     *
     * @param ConfigurationResolver $configurationResolver
     * @param ListTypeResolver      $listTypeResolver
     * @param SelectorTypeLocator   $selectorTypeLocator
     * @param TranslatorInterface   $translator
     */
    public function __construct(
        ConfigurationResolver $configurationResolver,
        ListTypeResolver $listTypeResolver,
        SelectorTypeLocator $selectorTypeLocator,
        ?TranslatorInterface $translator
    ) {
        $this->configuration = $configurationResolver;
        $this->typeResolver = $listTypeResolver;
        $this->selectorTypeLocator = $selectorTypeLocator;
        $this->translator = $translator;
    }

    /**
     * @param FieldView $fieldView
     *
     * @return string
     */
    public function getHeaderValue(FieldView $fieldView): string
    {
        $value = $fieldView->getLabel();
        $translate = $this->configuration->getTranslate();

        if ($translate) {
            $domain = $this->configuration->getTranslationDomain();
            $value = $this->translate($value, $domain);
        }

        return $value;
    }

    /**
     * @param FieldView $fieldView
     * @param array     $data
     *
     * @return mixed
     */
    public function getFieldValue(FieldView $fieldView, array $data)
    {
        $listField = $fieldView->getListField();
        $value = $this->selectorTypeLocator->get($listField->getOption(ListField::OPTION_SELECTOR))
            ->getValue($data, $listField->getId());

        $value = $this->processFieldValue($listField, $value);
        $value = $this->translateValue($listField, $value);

        return $value;
    }

    /**
     * @param ListField    $listField
     * @param mixed        $value
     *
     * @return mixed
     */
    private function processFieldValue(ListField $listField, $value)
    {
        if ($callable = $listField->getOption(ListField::OPTION_VALUE)) {
            $value = $callable($value, $this->typeResolver->getTypeName());
        } elseif ($type = $listField->getType()) {
            $value = $type->getValue($value, $this->typeResolver->getTypeName());
        }

        return $value;
    }

    /**
     * @param ListField $listField
     * @param mixed     $value
     *
     * @return mixed
     */
    private function translateValue(ListField $listField, $value)
    {
        if ((true === $listField->getOption(ListField::OPTION_TRANSLATE) && is_string($value)) ||
            (null === $value && true === $listField->getOption(ListField::OPTION_TRANSLATE_NULL))
        ) {
            $domain = $listField->getOption(ListField::OPTION_TRANSLATION_DOMAIN);
            $prefix = $listField->getOption(ListField::OPTION_TRANSLATION_PREFIX);

            $value = $this->translate(sprintf('%s%s', $prefix, $value), $domain);
        }

        return $value;
    }

    /**
     * @param string      $id
     * @param string|null $domain
     *
     * @return string|null
     */
    private function translate(string $id, ?string $domain): ?string
    {
        if (!$this->translator) {
            throw ListException::missingTranslator();
        }

        return $this->translator->trans($id, [], $domain);
    }
}