<?php


namespace EasySwoole\Annotation;


use Doctrine\Common\Annotations\AnnotationReader;

class Annotation
{
    protected $parserTagList = [];
    protected $aliasMap = [];
    protected $strictMode = false;

    function __construct(array $parserTagList = [])
    {
        $this->parserTagList = $parserTagList;
    }

    function addAlias(string $alias,string $realTagName)
    {
        $this->aliasMap[$alias] = $realTagName;
        return $this;
    }

    public function strictMode(bool $is)
    {
        $this->strictMode = $is;
        return $this;
    }


    function addParserTag(AbstractAnnotationTag $annotationTag):Annotation
    {
        $name = $annotationTag->tagName();
        if(isset($this->aliasMap[$name])){
            throw new Exception("tag alias name {$name} and tag name is duplicate");
        }
        $this->parserTagList[$name] = $annotationTag;
        return $this;
    }

    function deleteParserTag(string $tagName):Annotation
    {
        unset($this->parserTagList[$tagName]);
        return $this;
    }


    function getAnnotation(\Reflector $ref):array
    {
        $ret = [];
        $reader = new AnnotationReader();
        if($ref instanceof \ReflectionMethod){
            $temp = $reader->getMethodAnnotations($ref);
        }else if($ref instanceof \ReflectionProperty){
            $temp = $reader->getPropertyAnnotations($ref);
        }else if($ref instanceof \ReflectionClass){
            $temp = $reader->getClassAnnotations($ref);
        }
        if(!empty($temp)) {
            $ret = $temp;
        }
        return $ret;
    }
}