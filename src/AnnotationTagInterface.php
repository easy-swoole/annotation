<?php


namespace EasySwoole\Annotation;


interface AnnotationTagInterface
{
    public function tagName():string;
    public function assetValue(?string $raw);
}