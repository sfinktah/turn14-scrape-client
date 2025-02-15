```
use Sfinktah\FuncTools\RepeatIfException;

function throw_exception($arg1, $arg2) {
    printf("throw_exception: %s %s\n", $arg1, $arg2);
    throw new Exception("throw_exception");
}
```

### Retry 3 times, with 60 seconds delay between each.  Pass arguments `arg1` and `arg2` to function.

### By closure
```
RepeatIfException::call(['GuzzleHttp\\Exception\\'], 60, 3, fn($arg1, $arg2) => throw_exception($arg1, $arg2), 'arg1', 'arg2');
```

### By function name
```
RepeatIfException::call(['GuzzleHttp\\Exception\\'], 60, 3, 'throw_exception', 'arg1', 'arg2');
```

### By static method name (i think)
```
RepeatIfException::call(['GuzzleHttp\\Exception\\'], 60, 3, ['class', 'method', 'arg1', 'arg2');
```

### By instance method (i think)
```
RepeatIfException::call(['GuzzleHttp\\Exception\\'], 60, 3, [$instance, 'method', 'arg1', 'arg2');
```

