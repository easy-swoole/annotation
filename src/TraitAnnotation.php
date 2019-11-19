<?php


namespace EasySwoole\Annotation;


trait TraitAnnotation
{
    function __construct()
    {
        var_dump('t');
    }

    protected function parserAnnotation($extraTags = [])
    {
        $annotation = new Annotation();
        foreach ($extraTags as $extraTag){
            $annotation->addParserTag($extraTag);
        }
    }
}