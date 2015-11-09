# mini-config

mini-config takes a list of files and directories, and becomes an ArrayAccess object with the parsed config data. 

Currently supports JSON, INI, PHP arrays, and XML out of the box.

## Usage

Here we create a new Config object.        
We can pass an optional array of targets (directories and file paths) to include
```
$config = new Config(['config', 'module/config.xml']);
```

At this point the `$config` object is up and running, with the data from the files in `config` and in `module/config.xml`

Now we're going to add more targets.
As you can see we can either add an array of targets or just one.
```
$config->addTarget('/anothermodule/config');
$config->addTarget(['config_ini', 'json']);
```
    
Instead of re-scanning and importing all the data everytime we add a target, we call the `refresh()` method to re-import the data:
```
$config->refresh();
```

We can access the data by treating the $config object as an array, i.e.

```
$config['database']['password'];
```

## Notes
One important note is that duplicate key values don't overwrite, they append values. So:
```
[
    'cat1' => [
        'key1' => 'value1',
    ]
]
```
and
```
[cat1]
key1=value2
```
results in:
```
[
    'cat1' => [
        'key1' => [
            'value1',
            'value2',
        ]
    ]
]
```