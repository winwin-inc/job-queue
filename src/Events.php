<?php

namespace winwin\jobQueue;

abstract class Events
{
    const WORKER_START = 'worker_start';

    const WORKER_STOP = 'worker_stop';

    const BEFORE_PROCESS_JOB = 'before_process_job';

    const AFTER_PROCESS_JOB = 'after_process_job';

    const JOB_FAILED = 'job_failed';

    const PROCESSOR_START = 'processor_start';

    const BEFORE_PROCESSOR_STOP = 'before_processor_stop';

    const AFTER_PROCESSOR_STOP = 'after_processor_stop';

    const BEFORE_PROCESSOR_RELOAD = 'before_processor_reload';

    const AFTER_PROCESSOR_RELOAD = 'after_processor_reload';
}
