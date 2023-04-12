CI is Code Inspection
===

 * Automatically inspection the classes all methods.
 * Automatically access testcases.

# Technical information

 * "OP_CI" is load ".gitmodule" file.
 * Target modules and units must be described in the ".gitmodule" file.
 * The class use "OP_CI" trait.
 * The "OP_CI" gets all the methods that the class has.
 * The "Config" files is place to "ci" directory. And the file name is the class name.

## Conceptual code

```php
//  ...
function( $op_obj ){
    //  ...
    $methods = $op_obj->CI_AllMethods();

    //  ...
    $ci_config = \OP\UNIT\CI\CIConfig( class_name($op_obj) );

    //  ...
    foreach( $methods as $method ){
        //  ...
        $args = $ci_config[$method];

        //  ...
        $op_obj->CI_Inspection($method, $args);
    }
}
```
