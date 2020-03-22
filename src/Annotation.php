<?php


namespace EasySwoole\Annotation;


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
        $this->aliasMap[strtolower($alias)] = $realTagName;
        return $this;
    }

    public function strictMode(bool $is)
    {
        $this->strictMode = $is;
        return $this;
    }


    function addParserTag(AbstractAnnotationTag $annotationTag):Annotation
    {
        $name = strtolower($annotationTag->tagName());
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
        $start = false;
        $temp = '';
        $tempList = explode(PHP_EOL,$doc);
        foreach ($tempList as $line){
            //补回去PHP_EOL
            $line = $line.PHP_EOL;
            //取出*
            $pos = strpos($line,'*');
            if($pos !== false){
                $line = substr($line,$pos +1);
            }
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
        $name = $item->getName();
        if(isset($this->aliasMap[strtolower($name)])){
            $name = $this->aliasMap[strtolower($name)];
            $item->setName($name);
        }
        if(isset($this->parserTagList[$name])){
            /** @var AbstractAnnotationTag $tag */
            $tag = clone $this->parserTagList[$name];
            $tag->assetValue($item->getValue());
            $result[$name][] = $tag;
        }else if($this->strictMode){
            throw new Exception("parser fail because of unregister tag name:{$name} in strict parser mode");
        }
    }

    public static function parserLine(string $line):?LineItem
    {
        //括号匹配
        $brackets = false;
        $value = '';
        $name = '';
        $len = strlen($line);
        $valueLen = 0;
        for ($i = 0;$i < $len;$i++){
            $char = $line[$i];
            if($brackets === false){
                if($char != '('){
                    $name .= $line[$i];
                }else{
                    $brackets = 1;
                    $value .= $line[$i];
                }
            }else{
                if($char == ')'){
                    $brackets--;
                }
                $value .= $line[$i];
                if($brackets === 0){
                    break;
                }
            }
        }
        //-2 是因为PHP_EOL 1  还有index 从0开始
        if(($brackets === 0) && ($i == ( $len - 2))){
            $item = new LineItem();
            $item->setName($name);
            //取出括号
            $value = substr($value,1,- 1);
            $item->setName($name);
            $item->setValue($value);
            return $item;
        }
        return null;
    }
}