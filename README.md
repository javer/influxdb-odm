InfluxDB Object Document Mapper (ODM)
=====================================

The InfluxDB ODM is a library that provides a PHP object mapping functionality for InfluxDB.

Version 2 of this library uses PHP client for the InfluxDB 2.x. Please take a look on forward compatibility description 
of client library for [InfluxDB 1.8 API compatibility](https://github.com/influxdata/influxdb-client-php#influxdb-18-api-compatibility)

[![Build Status](https://secure.travis-ci.org/javer/influxdb-odm.png?branch=master)](http://travis-ci.org/javer/influxdb-odm)

Installation
============

Make sure Composer is installed globally, as explained in the
[installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

Open a command console, enter your project directory and execute:

```console
$ composer require javer/influxdb-odm
```

Usage
=====

The best way to understand the InfluxDB ODM is to see it in action.
In this section, you'll walk through each step needed to start persisting measurements to and from InfluxDB.

Create a Measurement Class with Mapping Information
---------------------------------------------------

Doctrine allows you to work with InfluxDB in a much more interesting way
than just fetching data back and forth as an array. Instead, Doctrine allows
you to persist entire *objects* to InfluxDB and fetch entire objects out of
InfluxDB. This works by mapping a PHP class and its properties to entries
of a InfluxDB Measurement.

For Doctrine to be able to do this, you have to create "metadata", or
configuration that tells Doctrine exactly how the `CpuLoad` class and its
properties should be *mapped* to InfluxDB. This metadata can be specified
directly inside the `CpuLoad` class via attributes:

```php
// src/Measurement/CpuLoad.php
namespace App\Measurement;

use Javer\InfluxDB\ODM\Mapping\Attributes as InfluxDB;

#[InfluxDB\Measurement(name: "cpu_load")]
class CpuLoad
{
    #[InfluxDB\Timestamp]
    private ?\DateTime $time = null;

    #[InfluxDB\Tag(name: "server_id")]
    private ?int $serverId = null;

    #[InfluxDB\Tag(name: "core_number")]
    private ?int $coreNumber = null;

    #[InfluxDB\Field(countable: true)]
    private ?float $load = null;
}
```

Create Measurement Manager
--------------------------

If you are not using `JaverInfluxDBBundle`, you have to create an instance of MeasurementManager:

```php
use InfluxDB2\Model\WritePrecision;
use Javer\InfluxDB\ODM\Client\ClientFactory;
use Javer\InfluxDB\ODM\Mapping\Driver\AttributeDriver;
use Javer\InfluxDB\ODM\Mapping\Driver\AttributeReader;
use Javer\InfluxDB\ODM\MeasurementManager;
use Javer\InfluxDB\ODM\Repository\RepositoryFactory;

$url = 'http://localhost:8086';
$bucket = 'metrics';
$mappingDir = 'src/Measurements';
$attributeDriver = new AttributeDriver(new AttributeReader(), [$mappingDir]);
$clientFactory = new ClientFactory();
$repositoryFactory = new RepositoryFactory();
$measurementManager = new MeasurementManager($attributeDriver, $clientFactory, $repositoryFactory, $url, $bucket, WritePrecision::NS);
```

Persisting Objects to InfluxDB
------------------------------

Now that you have a mapped `CpuLoad` measurement complete with getter and
setter methods, you're ready to persist data to InfluxDB:

```php
use App\Measurement\CpuLoad;
use Javer\InfluxDB\ODM\MeasurementManager;

$cpuLoad = new CpuLoad();
$cpuLoad->setTime(new DateTime());
$cpuLoad->setServerId(42);
$cpuLoad->setCoreNumber(0);
$cpuLoad->setLoad(3.14);

/** @var MeasurementManager $measurementManager */
$measurementManager->persist($cpuLoad);
```

Fetching Objects from InfluxDB
------------------------------

Fetching an object back out of InfluxDB is also possible:

```php
$cpuLoad = $measurementManager->getRepository(CpuLoad::class)->find($time);
```

When you query for a particular type of object, you always use what's known
as its "repository". You can think of a repository as a PHP class whose only
job is to help you fetch objects of a certain class. You can access the
repository object for a measurement class via:

```php
$repository = $measurementManager->getRepository(CpuLoad::class);
```

Once you have your repository, you have access to all sorts of helpful methods:

```php
// query by the time
$cpuLoad = $repository->find($time);

// find *all* CPU metrics
$cpuLoads = $repository->findAll();

// find a group of CPU metrics for the particular server
$cpuLoads = $repository->findBy(['serverId' => 42]);
```

You can also take advantage of the useful ``findBy()`` and ``findOneBy()`` methods
to easily fetch objects based on multiple conditions:

```php
// query for one cpuLoad matching by serverId and coreNumber
$cpuLoad = $repository->findOneBy(['serverId' => 42, 'coreNumber' => 3]);

// query for all cpuLoads matching the serverId, ordered by time desc
$cpuLoads = $repository->findBy(
    ['serverId' => 42],
    ['time' => 'DESC']
);
```

Updating an Object
------------------

Once you've fetched an object from Doctrine, let's try to update it.

```php
$cpuLoad->setLoad(2.54);

$measurementManager->persist($cpuLoad);
```

Please note that you cannot update value of Timestamp or Tag field, only simple Field can be updated.

Deleting an Object
------------------

Deleting an object is very similar, but requires a call to the ``remove()`` method of the measurement manager:

```php
$measurementManager->remove($cpuLoad);
```

Querying for Objects
--------------------

As you saw above, the built-in repository class allows you to query for one
or many objects based on any number of different parameters. When this is
enough, this is the easiest way to query for measurements. You can also create
more complex queries.

Using the Query Builder
-----------------------

InfluxDB ODM ships with a query "Builder" object, which allows you to construct
a query for exactly which measurements you want to return. If you use an IDE,
you can also take advantage of auto-completion as you type the method names.
From inside a controller:

```php
$cpuLoads = $measurementManager->createQuery(CpuLoad::class)
    ->where('serverId', 42)
    ->orderBy('time', 'ASC')
    ->limit(10)
    ->getResult();
```

Custom Repository Classes
-------------------------

In the previous section, you began constructing and using more complex queries
from inside a controller. In order to isolate, test and reuse these queries,
it's a good idea to create a custom repository class for your measurement and
add methods with your query logic there.

To do this, add the name of the repository class to your mapping definition.

```php
// src/Measurement/CpuLoad.php
namespace App\Measurement;

use App\Repository\CpuLoadRepository;
use Javer\InfluxDB\ODM\Mapping\Attributes as InfluxDB;

#[InfluxDB\Measurement(name: "cpu_load", repositoryClass: CpuLoadRepository::class)]
class CpuLoad
{
    // ...
}
```

You have to create the repository in the namespace indicated above. Make sure it
extends the default `MeasurementRepository`. Next, add a new method -
`findAllOrderedByTimeDesc()` - to the new repository class. This method will query
for all of the `CpuLoad` measurements, ordered by time desc.

```php
// src/Repository/CpuLoadRepository.php
namespace App\Repository;

use Javer\InfluxDB\ODM\Repository\MeasurementRepository;

class CpuLoadRepository extends MeasurementRepository
{
    public function findAllOrderedByTimeDesc(): array
    {
        return $this->createQuery()
            ->orderBy('time', 'DESC')
            ->getResult();
    }
}
```

You can use this new method like the default finder methods of the repository:

```php
$cpuLoads = $measurementManager->getRepository(CpuLoad::class)
    ->findAllOrderedByTimeDesc();
```

When using a custom repository class, you still have access to the default
finder methods such as `find()` and `findAll()`.
