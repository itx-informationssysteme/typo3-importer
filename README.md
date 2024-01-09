# Importer Extension

## Usage

### Running the queue worker

To run the queue worker for the importer extension, run the following command:

    vendor/bin/typo3cms importer:queue-worker

This will run a single queue worker process. You can run multiple queue workers in parallel to speed up the import.

To have them run automatically in multiple instances, you can use the following command:

    vendor/bin/typo3cms importer:queue-worker-manager <optional worker count>

### Importing data

To create a new import, you need to create 3 files:

1. **Producer class**

   The producer class should inherit from the Itx\Importer\Command\Producer\AbstractJobProducer class.
   Inside the producer class you make the api calls to get information about the data you want to import.
   Finally, you will need to use the generateJobs method to create the jobs that will be processed by the queue worker.

2. **Payload class**

   Use a custom payload class to pass typesafe data to the consumer class. This class doesn't getters and setters.

3. **Consumer class**

   The consumer class will be called by the queue worker and needs to implement the ConsumerInterface.
   It will receive the payload class as a parameter.
   Inside the consumer class you can use the data from the payload class to import the data into your database.
   You don't need to register the consumer, it will be automatically detected.

When all jobs were processed the importer will call the finish method on the producer class. Use this method to clean up
temporary files or to delete old records.

You will need to register the producer class as a command in a Service.yaml. The naming will be as follows

        importer:producer:<importer type name here>

Make sure to use the same name as for the producer class importer type name method.

Note: If you use the importer extension in another extension you will need to add the following configuration to your extension's
Services.yaml (just below _defaults):

    _instanceof:
        Itx\Importer\Consumer\ConsumerInterface:
            tags: [ 'queue_consumer' ]
            lazy: true
        Itx\Importer\Command\Producer\AbstractJobProducer:
            tags: [ 'queue_producer' ]
            lazy: true

That's it! You can now set up as many queue workers as you want as well as configure a scheduler task for the producer and the
import will be executed automatically.

## Race conditions and locking

Because every job could be run in parallel by the runtime, there is a chance that the same records might be tried to be imported
multiple times.
To prevent that from happening, you can use the `Itx\Importer\Service\LockingService::createLock()` method. This method returns a
Lock object, on which you can call the `acquire()` method.
Make sure to call it with the `blocking=true` argument. Also make sure to call the `release()` method on the lock object when you
are done with it.
The lock is autoconfigured to be released when the process ends, but it's good practice to release it manually.

## Child jobs

You can create jobs that are discovered while a job is being processed. This is useful if you have to import hierarchical data.

## Statistics

To create statistics for your import, you can use the `Itx\Importer\Service\StatisticsService::addStatistic()` method.
The method can run in parallel and will automatically aggregate the statistics for you.
To have data in different places make sure to use the same table name and record name for the same data. The values will be added
together, when called multiple times.

You can see the resulting statistics records in the backend module.
