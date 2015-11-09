# mini-config

mini-config takes a list of files and directories, and becomes an ArrayAccess object with the parsed config data. 

Currently supports JSON, INI, PHP arrays, and XML out of the box.

Documentation is avaialble at: [http://abreksa4.github.io/mini-config-docs/](http://abreksa4.github.io/mini-config-docs/)

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
$config->addTarget(['config_ini', 'config_yaml']);
```

Now we'll add a YAML handler. 

Notice we can register an array of extensions to one handler, we can also specify a single extension. Extensions are case-
sesitive.

(Note, we'd need to have the YAML PHP extension installed)

```
$config->registerHandler(['yaml', 'YAML'], 
       function($file){
            return yaml_parse_file($file);
       }
);
```
    
Instead of re-scanning and importing all the data everytime we add a target, we call the `refresh()` method to re-import the data:
```
$config->refresh();
```

We can access the data by treating the `$config` object as an array, i.e.

```
$config['database']['password'];
```

## Note
If two config files have the same key, the values are merged into an array. So:

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