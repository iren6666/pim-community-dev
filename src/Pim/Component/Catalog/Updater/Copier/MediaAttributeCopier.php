<?php

namespace Pim\Component\Catalog\Updater\Copier;

use Akeneo\Component\FileStorage\File\FileFetcherInterface;
use Akeneo\Component\FileStorage\File\FileStorerInterface;
use Akeneo\Component\FileStorage\FilesystemProvider;
use Pim\Component\Catalog\Builder\EntityWithValuesBuilderInterface;
use Pim\Component\Catalog\FileStorage;
use Pim\Component\Catalog\Model\AttributeInterface;
use Pim\Component\Catalog\Model\EntityWithValuesInterface;
use Pim\Component\Catalog\Model\ProductInterface;
use Pim\Component\Catalog\ProductValue\MediaProductValueInterface;
use Pim\Component\Catalog\Validator\AttributeValidatorHelper;

/**
 * Copy a media value attribute in other media value attribute
 *
 * @author    Julien Janvier <jjanvier@akeneo.com>
 * @copyright 2015 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class MediaAttributeCopier extends AbstractAttributeCopier
{
    /** @var FileFetcherInterface */
    protected $fileFetcher;

    /** @var FileStorerInterface */
    protected $fileStorer;

    /** @var FilesystemProvider */
    protected $filesystemProvider;

    /**
     * @param EntityWithValuesBuilderInterface $entityWithValuesBuilder
     * @param AttributeValidatorHelper         $attrValidatorHelper
     * @param FileFetcherInterface             $fileFetcher
     * @param FileStorerInterface              $fileStorer
     * @param FilesystemProvider               $filesystemProvider
     * @param array                            $supportedFromTypes
     * @param array                            $supportedToTypes
     */
    public function __construct(
        EntityWithValuesBuilderInterface $entityWithValuesBuilder,
        AttributeValidatorHelper $attrValidatorHelper,
        FileFetcherInterface $fileFetcher,
        FileStorerInterface $fileStorer,
        FilesystemProvider $filesystemProvider,
        array $supportedFromTypes,
        array $supportedToTypes
    ) {
        parent::__construct($entityWithValuesBuilder, $attrValidatorHelper);

        $this->fileFetcher = $fileFetcher;
        $this->fileStorer = $fileStorer;
        $this->filesystemProvider = $filesystemProvider;
        $this->supportedFromTypes = $supportedFromTypes;
        $this->supportedToTypes = $supportedToTypes;
    }

    /**
     * {@inheritdoc}
     */
    public function copyAttributeData(
        EntityWithValuesInterface $fromEntityWithValues,
        EntityWithValuesInterface $toEntityWithValues,
        AttributeInterface $fromAttribute,
        AttributeInterface $toAttribute,
        array $options = []
    ) {
        $options = $this->resolver->resolve($options);
        $fromLocale = $options['from_locale'];
        $toLocale = $options['to_locale'];
        $fromScope = $options['from_scope'];
        $toScope = $options['to_scope'];

        $this->checkLocaleAndScope($fromAttribute, $fromLocale, $fromScope);
        $this->checkLocaleAndScope($toAttribute, $toLocale, $toScope);

        $this->copySingleValue(
            $fromEntityWithValues,
            $toEntityWithValues,
            $fromAttribute,
            $toAttribute,
            $fromLocale,
            $toLocale,
            $fromScope,
            $toScope
        );
    }

    /**
     * Copies a single media value and handle the file associated to it.
     *
     * @param EntityWithValuesInterface $fromEntityWithValues
     * @param EntityWithValuesInterface $toEntityWithValues
     * @param AttributeInterface        $fromAttribute
     * @param AttributeInterface        $toAttribute
     * @param string                    $fromLocale
     * @param string                    $toLocale
     * @param string                    $fromScope
     * @param string                    $toScope
     */
    protected function copySingleValue(
        EntityWithValuesInterface $fromEntityWithValues,
        EntityWithValuesInterface $toEntityWithValues,
        AttributeInterface $fromAttribute,
        AttributeInterface $toAttribute,
        $fromLocale,
        $toLocale,
        $fromScope,
        $toScope
    ) {
        $fromValue = $fromEntityWithValues->getValue($fromAttribute->getCode(), $fromLocale, $fromScope);
        if (null !== $fromValue) {
            $file = null;
            if (null !== $fromValue->getData()) {
                $filesystem = $this->filesystemProvider->getFilesystem(FileStorage::CATALOG_STORAGE_ALIAS);
                $rawFile = $this->fileFetcher->fetch($filesystem, $fromValue->getData()->getKey());
                $file = $this->fileStorer->store($rawFile, FileStorage::CATALOG_STORAGE_ALIAS, false);

                $file->setOriginalFilename($fromValue->getData()->getOriginalFilename());
            }

            $this->entityWithValuesBuilder->addOrReplaceValue(
                $toEntityWithValues,
                $toAttribute,
                $toLocale,
                $toScope,
                null !== $file ? $file->getKey() : null
            );
        }
    }
}
