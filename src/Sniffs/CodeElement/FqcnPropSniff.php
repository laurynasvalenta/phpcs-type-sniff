<?php

namespace Gskema\TypeSniff\Sniffs\CodeElement;

use PHP_CodeSniffer\Files\File;
use Gskema\TypeSniff\Core\CodeElement\Element\AbstractFqcnPropElement;
use Gskema\TypeSniff\Core\CodeElement\Element\ClassPropElement;
use Gskema\TypeSniff\Core\CodeElement\Element\CodeElementInterface;
use Gskema\TypeSniff\Core\CodeElement\Element\TraitPropElement;
use Gskema\TypeSniff\Core\DocBlock\Tag\VarTag;
use Gskema\TypeSniff\Core\DocBlock\UndefinedDocBlock;
use Gskema\TypeSniff\Core\Type\Common\ArrayType;
use Gskema\TypeSniff\Core\Type\Common\UndefinedType;
use Gskema\TypeSniff\Core\Type\DocBlock\CompoundType;

class FqcnPropSniff implements CodeElementSniffInterface
{
    /**
     * @inheritDoc
     */
    public function configure(array $config): void
    {
        // nothing to do
    }

    /**
     * @inheritDoc
     */
    public function register(): array
    {
        return [
            ClassPropElement::class,
            TraitPropElement::class,
        ];
    }

    /**
     * @inheritDoc
     * @param AbstractFqcnPropElement $prop
     */
    public function process(File $file, CodeElementInterface $prop): void
    {
        // @TODO Infer type from initial value?
        $docBlock = $prop->getDocBlock();

        /** @var VarTag|null $varTag */
        $varTag = $docBlock->getTagsByName('var')[0] ?? null;
        $varType = $varTag ? $varTag->getType() : null;

        if ($docBlock instanceof UndefinedDocBlock) {
            $file->addWarningOnLine(
                'Add PHPDoc for property $'.$prop->getPropName(),
                $prop->getLine(),
                'FqcnPropSniff'
            );
        } elseif (null === $varTag) {
            $file->addWarningOnLine(
                'Add @var tag for property $'.$prop->getPropName(),
                $prop->getLine(),
                'FqcnPropSniff'
            );
        } elseif ($varType instanceof UndefinedType) {
            $file->addWarningOnLine(
                'Add type hint to @var tag for property $'.$prop->getPropName(),
                $prop->getLine(),
                'FqcnPropSniff'
            );
        } elseif ($varType instanceof ArrayType
              || ($varType instanceof CompoundType && $varType->containsType(ArrayType::class))
        ) {
            $subject = '$'.$prop->getPropName();
            $file->addWarningOnLine(
                'Replace array type with typed array type in PHPDoc for '.$subject.'. Use mixed[] for generic arrays.',
                $prop->getLine(),
                'FqcnPropSniff'
            );
        }

        if ($varTag && null !== $varTag->getParamName()) {
            $file->addWarningOnLine(
                'Remove property name $'.$varTag->getParamName().' from @var tag',
                $prop->getLine(),
                'FqcnPropSniff'
            );
        }
    }
}
