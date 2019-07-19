<?php


namespace EasySwoole\Annotation;


class Annotation
{
    protected $parserTagList = [];
    protected $strictMode = false;

    function __construct(array $parserTagList = [])
    {
        $this->parserTagList = $parserTagList;
    }

    public function strictMode(?bool $strict = null)
    {
        if($strict !== null){
            $this->strictMode = $strict;
        }
        return $this->strictMode;
    }

    function addParserTag(AnnotationTagInterface $annotationTag):Annotation
    {
        $this->parserTagList[$annotationTag->tagName()] = $annotationTag;
        return $this;
    }

    function deleteParserTag(string $tagName):Annotation
    {
        unset($this->parserTagList[$tagName]);
        return $this;
    }

    function getPropertyAnnotation(\ReflectionProperty $property):array
    {
        $doc = $property->getDocComment();
        $doc = $doc ? $doc : '';
        return $this->parser($doc);
    }

    function getClassMethodAnnotation(\ReflectionMethod $method):array
    {
        $doc = $method->getDocComment();
        $doc = $doc ? $doc : '';
        return $this->parser($doc);
    }

    private function parser(string $doc):array
    {
        $result = [];
        $tempList = explode(PHP_EOL,$doc);
        foreach ($tempList as $line){
            $line = trim($line);
            $pos = strpos($line,'@');
            if($pos !== false && $pos <= 3){
                $lineItem = self::parserLine($line);
                if($lineItem){
                    if(isset($this->parserTagList[$lineItem->getName()])){
                        /** @var AnnotationTagInterface $obj */
                        $obj = clone $this->parserTagList[$lineItem->getName()];
                        $obj->assetValue($lineItem->getValue());
                        $result[$lineItem->getName()][] = $obj ;
                    }else if($this->strictMode){
                        throw new Exception("parser fail because of unregister tag name:{$lineItem->getName()} in strict parser mode");
                    }
                }else if($this->strictMode){
                    throw new Exception("parser fail for data:{$line} in strict parser mode");
                }
            }
        }
        return $result;
    }

    public static function parserLine(string $line):?LineItem
    {
        $pattern = '/@([a-zA-Z][0-9a-zA-Z_]*?)\((.*)\)/';
        preg_match($pattern, $line,$match);
        if(is_array($match) && (count($match) == 3)){
            $item = new LineItem();
            $item->setName($match[1]);
            $item->setValue($match[2]);
            return $item;
        }else{
            return null;
        }
    }
}