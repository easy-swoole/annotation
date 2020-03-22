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


    function getAnnotation(\Reflector $method):array
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
        $item->setName($name);
        $item->setValue($line);
        return $item;
    }
}