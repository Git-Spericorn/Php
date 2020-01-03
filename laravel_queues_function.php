#1Laravel Queues

#step 1
php artisan queue:table
php artisan make:job PullPubmed --queued



#step2
Pull pubmed Job File



<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
# to call pythonscript
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
# to call pythonscript
use App\FilterType;
use App\Filter;

class PullPubMed implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */

    protected $filter;
    public function __construct(Filter $filter)
    {
        $this->filter= $filter;

    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {	//python script in below path is executed for pulling certain data from pubmed servers

        \Log::info('PullPubMed Queue with Filter ID: ' . $this->filter->id);

        $python_path=public_path().'/python/pubmed_abstract/pubmed_engine.py';

         $variables='{"filter_id":"'.$this->filter->id.'"}';

         $process = new Process("python $python_path '$variables'");
         $process->run();
         if (!$process->isSuccessful()) {
              throw new ProcessFailedException($process);
          }
    }
}


#step3

Controller:Call job function.

Usially laravel queues can be called in cronjobs.

use App\Jobs\PullPubMed;


class PostController extends Controller{
	public construct(){
			$this->dispatch(new PullPubMed($filter)); //pull pubmed queue
		
	}

