# annotation

## 实现原理
- 约定
    - 每个注解行为@字符开头，格式为
    ```@METHOD(ARGS)```
- 执行流程如下：
    - 利用php反射得到注释信息
    - 用explode用PHP_EOL分割每行
    - 解析每行的数据，若存在对应的METHOD AnnotationTagInterface ，则把解析得到的ARGS传递给 AnnotationTagInterface 中的 assetValue 方法，
      用户可以在该方法中对解析得到的值进行自定义解析。
    - 若为严格模式，如果解析不到正确的数值，则报错。  

## 例子
```
use EasySwoole\Annotation\Annotation;
use EasySwoole\Annotation\AnnotationTagInterface;

class A
{
    /** @var  */
    protected $a;

    /**
     * @param(name=a,type=string,value=2)
     * @param(name=b)
     * @timeout(0.5)
     * @fuck(easyswoole)
     * 这是我的其他说明啊啊啊啊啊
     */
    function test()
    {

    }
}





/*
 * 定义param渲染方法
 */

class param implements AnnotationTagInterface
{

    public function tagName(): string
    {
        return 'param';
    }

    public function assetValue(?string $raw)
    {
        $list = explode(',',$raw);
        foreach ($list as $item){
            parse_str($item,$ret);
            foreach ($ret as $key => $value){
                $this->$key = $value;
            }
        }
    }
}

/*
 * 定义timeout渲染方法
 */

class timeout implements AnnotationTagInterface
{
    public $timeout;

    public function tagName(): string
    {
        return 'timeout';
    }

    public function assetValue(?string $raw)
    {
        $this->timeout = floatval($raw);
    }
}

/*
 * 实例化渲染器,并注册要解析的渲染方法
 */
$annotation = new Annotation();
$ref = new \ReflectionClass(A::class);
//不注册fuck 解析
$annotation->addParserTag(new param());
$annotation->addParserTag(new timeout());

$list = $annotation->getClassMethodAnnotation($ref->getMethod('test'));

foreach ($list['param'] as $item){
    var_dump((array)$item);
}

foreach ($list['timeout'] as $item){
    var_dump((array)$item);
}
```

> 注释每行前3个字符若存在@,说明该行为需要解析注释行，默认为非严格模式，未注册的tag信息不会解析，严格模式下，若无法解析则会抛出异常。

## IDE支持