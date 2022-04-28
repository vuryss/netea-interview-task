# NETEA Interview task

## Deviation from REST API

One of the requirements in the interview task is 

> The endpoint should be RESTful and naming recommendations compliant
> The endpoint should be designed following the best practices

However, REST defines operations on entities while here we just calculate progress completely using data from request 
parameters. This will not be a REST endpoint, but rather just an endpoint accepting a number of parameters.

If we want this to be following REST practices we will need to have a persistence and make a process like this:

1. Creating a learning content

```http request
POST /learning-content

{"duration": 1000}
```

Which can respond with
```
201 Created

{"id": "410b28c5-377d-46e9-87a2-322f055d3150"}
```

2. Then create an assignment for that learning content

```http request
POST /assignments

{"startDate": "2022-04-01T07:20:50+02:00", "endDate": "2022-04-10T12:30:00+02:00", "content": "/learning-content/410b28c5-377d-46e9-87a2-322f055d3150"}
```

Which can return:

```
201 Created

{"id": "c4bce586-51e3-45a0-8753-91fa4af931cd"}
```

3. Add progress to the assignment

```http request
PATCH /assignments/c4bce586-51e3-45a0-8753-91fa4af931cd

{"progressPercentage": 23}
```

4. And finally retrieve a status report

```http request
GET /assignments/c4bce586-51e3-45a0-8753-91fa4af931cd/status-report

{"progress_status":"on track","expected_progress":xxx,"needed_daily_learning_time":xxx}
```

This would be the RESTful way to do such API.

But from the assignment I understand that a single endpoint with 4 parameters is required, so the best I can do is to
make an endpoint with URL like `/assignment/status-report/{learningDuration}/{completedPercentage}/{startDate}/{endDate}`

Which will jump directly to point 4 from above workflow and calculate the status report.

## Assumptions

As some conditions are not specified in the assignment, here are some assumptions that I made, which can easily be 
adjusted if needed.

- Even if the assignment is given at the end of the day, it counts the first day as full day.
- Even if the assignment ends very early on the end date, it counts the final day as full day.
- Expected daily learning time is rounded to the next higher number in seconds
- Days are calculated as a full day, so the expected progress will be the same no matter when during the day it's 
calculated

On future assignments

- If the assignment haven't started yet, the status is 'on track', the calculated daily learning accounts only for the 
assignment days and does not start earlier
- If the assignment haven't started yet, and you have a positive progress, an error is returned


Currently, there is so way to say whether you have finished watching today or not.
That's why I use the following assumptions:

- The expected progress includes the current day, as if it has fully passed.
This means you are expected to have finished the assignment for today.
- The needed daily learning time is given starting from today to the final day of the task inclusive.
This means that if you have finished watching the recommended time for today, it will still recommend to watch more for the current day.

## Requirements

- PHP 8.1
- Docker (Optional)

## Building the project

### Container build

Make sure you are in the root project directory and run:

`docker build . -t netea-interview-task`

### Installing project dependencies

`docker run -itu user --rm -v "$PWD":/app -w /app netea-interview-task composer install`

## How to run the project

Make sure you have executed the required steps in [Building the project](#building-the-project)

`docker run -itu user -p 8000:8000 --rm -v "$PWD":/app -w /app netea-interview-task symfony server:start`

Now the project should be accessible on http://127.0.0.1:8000

Endpoint documentation available at: http://127.0.0.1:8000/api/doc

## Executing the automated tests

`docker run -itu user --rm -v "$PWD":/app -w /app --add-host=host.docker.internal:host-gateway netea-interview-task bin/test`

After executing the tests, the code coverage report will be available under tests/_output/coverage directory.

## Debug the project (on linux only)

Add to the docker run command:

`--add-host=host.docker.internal:host-gateway` so xdebug can connect to the host IDE

## Static code analysis tools

### PHP Mess Detector

`docker run -itu user --rm -v "$PWD":/app -w /app netea-interview-task vendor/bin/phpmd src text phpmd.xml`

### PHP Code Sniffer

`docker run -itu user --rm -v "$PWD":/app -w /app netea-interview-task vendor/bin/phpcs -p`

### Psalm level 3

`docker run -itu user --rm -v "$PWD":/app -w /app --add-host=host.docker.internal:host-gateway netea-interview-task vendor/bin/psalm`
