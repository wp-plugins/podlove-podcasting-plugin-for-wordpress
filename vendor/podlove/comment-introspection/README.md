# Podlove Comment Introspection

PHP library providing a toolkit to parse class and method comments.

## Usage

```php
use Podlove\Comment\Comment;

class ExampleClass {

    /**
     * A Title
     *
     * A multiline
     * description.
     * 
     * @tag1
     * @tag2 tag2 description
     */
    public function foo() {
        return "bar";
    }
}

$reflectionClass = new ReflectionClass("ExampleClass");
$methods = $reflectionClass->getMethods();
$parsedMethods = array_map(function($method) {
    $c = new Comment($method->getDocComment());
    $c->parse();

    return [
        'methodname'  => $method->name,
        'title'       => $c->getTitle(),
        'description' => $c->getDescription(),
        'tags'        => $c->getTags()
        /**
         * You can also access specific tags like so:
         * $c->getTag('tag1') or
         * $c->getTags('param') if you use one tag multiple times
         */
    ];
}, $methods);
print_r($parsedMethods);

/* =>

Array
(
    [0] => Array
        (
            [methodname] => foo
            [title] => A Title
            [description] => A multiline
description.

            [tags] => Array
                (
                    [0] => Array
                        (
                            [name] => tag2
                            [description] => tag2 description
                            [line] => 6
                        )

                    [1] => Array
                        (
                            [name] => tag1
                            [description] =>
                            [line] => 5
                        )

                )

        )

)
*/
```