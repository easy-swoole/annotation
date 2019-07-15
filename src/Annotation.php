<?php


namespace EasySwoole\Annotation;


class Annotation
{
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
        $label = false;
        $temp = '';
        $list = [];
        $final = [];
        for($i = 0;$i < strlen($doc);$i++){
            if($doc[$i] == '@'){
                $temp = '';
                $label = true;
            }else if($label){
                if($doc[$i] == PHP_EOL){
                    $list[] = trim($temp);
                    $label = false;
                }else{
                    $temp = $temp.$doc[$i];
                }
            }
        }
        foreach ($list as $item){
            if(in_array($item[0],['(',')'])){
                continue;
            }
            $func = '';
            $arg = '';
            $label = 0;
            $finaleArg = new \stdClass();
            for($i = 0;$i < strlen($item);$i++){
                if($label == 0){
                    if($item[$i] != '('){
                        $func = $func.$item[$i];
                    }else{
                        $label = 1;
                    }
                }else if($label == 1){
                    if($item[$i] != ')'){
                        $arg = $arg.$item[$i];
                    }else{
                        $label = 0;
                        break;
                    }
                }
            }
            if($label != 0){
                continue;
            }
            $args = explode(',',$arg);
            foreach ($args as $argStr){
                parse_str($argStr,$array);
                foreach ($array as $key => $v){
                    $finaleArg->$key = $v;
                }
            }
            $final[$func][] = $finaleArg;
        }
        return $final;
    }
}