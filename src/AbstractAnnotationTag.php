<?php


namespace EasySwoole\Annotation;


abstract class AbstractAnnotationTag
{
    abstract public function tagName():string;
    abstract public function assetValue(?string $raw);
}