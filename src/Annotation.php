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

    private function parser(string $doc):array
    {
        $result = [];
        $start = false;
        $temp = '';
        $tempList = explode(PHP_EOL,$doc);
        foreach ($tempList as $line){
            //补回去PHP_EOL
            $line = $line.PHP_EOL;
            if(!$start){
                $pos = strpos($line,'@');
                if($pos !== false){
                    $start = true;
                    //取出 到@符号(包括)之前的字符串
                    $temp .= substr($line,$pos + 1);
                    $ret = static::parserLine($temp);
                    if($ret){
                        $this->handleLineItem($ret,$result);
                        $temp = '';
                        $start = false;
                    }
                }
            }else{
                $pos = strpos($line,'*');
                if($pos !== false){
                    $line = substr($line,$pos + 1);
                }
                $temp .= $line;
                $ret = static::parserLine($temp);
                if($ret){
                    $this->handleLineItem($ret,$result);
                    $temp = '';
                    $start = false;
                }
            }
        }
        return $result;
    }

    private function handleLineItem(LineItem $item,array &$result)
    {
        $aliasHit = false;
        $name = $item->getName();
        //如果有别名，则找出真实tag
        if(isset($this->aliasMap[$name])){
            $aliasHit = $name;
            $name = $this->aliasMap[$name];
            $item->setName($name);
        }
        if(isset($this->parserTagList[$name])){
            /** @var AbstractAnnotationTag $tag */
            $tag = clone $this->parserTagList[$name];
            $tag->assetValue($item->getValue());
            $result[$name][] = $tag;
            if($aliasHit){
                //如果是别名命中，也要允许可以找到这个别名参数
                if(isset($this->parserTagList[$aliasHit])){
                    $tag = clone $this->parserTagList[$aliasHit];
                }else{
                    $tag = clone $this->parserTagList[$name];
                }
                $tag->assetValue($item->getValue());
                $result[$aliasHit][] = $tag;
            }
        }else if($this->strictMode){
            throw new Exception("parser fail because of unregister tag name:{$name} in strict parser mode");
        }
    }

    public static function parserLine(string $line):?LineItem
    {
        if(substr_count($line,'(') != substr_count($line,')')){
            return null;
        }
        //先找出name
        $pos = strpos($line,'(');
        if($pos <= 0){
            return null;
        }
        $name = trim(substr($line,0,$pos),' ');
        if(empty($name)){
            return null;
        }
        $line = substr($line,$pos + 1);
        $pos = strrpos($line,')');
        if($pos === false){
            return null;
        }
        $line = substr($line,0,$pos);
        $item = new LineItem();
        //EasySwoole\HttpAnnotation\AnnotationTag\DocTag\Auth全路径支持
        $name = explode('\\',$name);
        $name = end($name);
        $item->setName($name);
        $item->setValue($line);
        return $item;
    }
}