<?php

namespace winwin\jobQueue;

abstract class Events
{
    const WORKER_START = 'job_queue.worker_start';

    const WORKER_STOP = 'job_queue.worker_stop';

    const WORKER_RELOAD = 'job_queue.worker_reload';

    const SERVER_ERROR = 'job_queue.server_error';

    const BEFORE_PROCESS_JOB = 'job_queue.before_process_job';

    const AFTER_PROCESS_JOB = 'job_queue.after_process_job';

    const JOB_FAILED = 'job_queue.job_failed';

    const PROCESSOR_START = 'job_queue.processor_start';

    const BEFORE_PROCESSOR_STOP = 'job_queue.before_processor_stop';

    const AFTER_PROCESSOR_STOP = 'job_queue.after_processor_stop';

    const BEFORE_PROCESSOR_RELOAD = 'job_queue.before_processor_reload';

    const AFTER_PROCESSOR_RELOAD = 'job_queue.after_processor_reload';

    const SCHEDULE_JOB_ADDED = 'job_queue.schedule_job_added';

    const BEFORE_SCHEDULE_JOB = 'job_queue.before_schedule_job';

    const AFTER_SCHEDULE_JOB = 'job_queue.after_schedule_job';

    const SCHEDULE_JOB_FAILED = 'job_queue.schedule_job_failed';

    const LOCK_CREATED = 'job_queue.lock_created';

    const LOCK_RELEASED = 'job_queue.lock_released';
}
