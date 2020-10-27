<?php

namespace App\Console\Commands;

use App\Covid\CovidApi;
use App\Models\CovidCase;
use App\Imports\CovidImport;
use App\Models\CovidCaseState;
use Illuminate\Support\Carbon;
use App\Imports\RegistryImport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class SeedCases extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'covid:seed-cases
                            {--source= : Where get dataset api or file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Retrieve a specific option...
        $source = $this->option('source');

        if ($source == 'file-covid') {
            $this->output->title('Iniciando Importação dos Dados - Covid');
            (new CovidImport)->withOutput($this->output)->import(Storage::path('caso.csv'));
            $this->output->success('Importação Concluida!');
        }

        if ($source == 'file-registry') {
            $this->output->title('Iniciando Importação dos Dados - Cartório');
            (new RegistryImport)->withOutput($this->output)->import(Storage::path('obito_cartorio.csv'));
            $this->output->success('Importação Concluida!');
        }

        if ($source == 'api') {
            $cases = new CovidApi;
            $this->output->title('Iniciando Importação dos Dados');

            $dataApi1 = collect($cases->getCaseContirmedAndDeath())->flatten(1);
            $bar1 = $this->output->createProgressBar(count($dataApi1));

            $this->line('Api brasil.io');
            collect($dataApi1)->each(function ($case) use ($bar1) {
                CovidCase::updateOrCreate([
                    'date' => $case['date'],
                    'city_ibge_code' => $case['city_ibge_code'],
                ], [
                    'state' => $case['state'],
                    'city' => $case['city'],
                    'place_type' => $case['place_type'],
                    'confirmed' => $case['confirmed'],
                    'deaths' => $case['deaths'],
                    'order_for_place' => $case['order_for_place'],
                    'is_last' => $case['is_last'],
                    'estimated_population_2019' => $case['estimated_population_2019'],
                    'estimated_population' => $case['estimated_population'],
                    'confirmed_per_100k_inhabitants' => $case['confirmed_per_100k_inhabitants'],
                    'death_rate' => $case['death_rate'],
                ]);

                $bar1->advance();
            });

            $bar1->finish();
            $this->line('Api brazil covid19');
            $dataApi2 = $cases->getCaseForStates();
            $bar2 = $this->output->createProgressBar(count($dataApi2));
            collect($dataApi2)->each(function ($case) use ($bar2) {
                CovidCaseState::updateOrCreate(['uid' => $case['uid']], [
                    'uf' => $case['uf'],
                    'state' => $case['state'],
                    'cases' => $case['cases'],
                    'deaths' => $case['deaths'],
                    'refuses' => $case['refuses'],
                    'datetime' => Carbon::parse($case['datetime'])->format('Y-m-d H:i:s'),
                    'suspects' => $case['suspects'],
                ]);
                $bar2->advance();
            });
            $bar2->finish();

            $dataApi3 = collect($cases->getRegistryDeaths())->flatten(1);
            $bar2 = $this->output->createProgressBar(count($dataApi3));

            $this->line('Api brasil.io - Registry');
            collect($dataApi3)->each(function ($case) use ($bar3) {
                CovidCase::updateOrCreate([
                    'date' => $case['date'],
                    'state' => $case['state'],
                ], [
                    'deaths_covid19' => $case['deaths_covid19'],
                    'deaths_indeterminate_2019' => $case['deaths_indeterminate_2019'],
                    'deaths_indeterminate_2020' => $case['deaths_indeterminate_2020'],
                    'deaths_others_2019' => $case['deaths_others_2019'],
                    'deaths_others_2020' => $case['deaths_others_2020'],
                    'deaths_pneumonia_2019' => $case['deaths_pneumonia_2019'],
                    'deaths_pneumonia_2020' => $case['deaths_pneumonia_2020'],
                    'deaths_respiratory_failure_2019' => $case['deaths_respiratory_failure_2019'],
                    'deaths_respiratory_failure_2020' => $case['deaths_respiratory_failure_2020'],
                    'deaths_sars_2019' => $case['deaths_sars_2019'],
                    'deaths_sars_2020' => $case['deaths_sars_2020'],
                    'deaths_septicemia_2019' => $case['deaths_septicemia_2019'],
                    'deaths_septicemia_2020' => $case['deaths_septicemia_2020'],
                    'deaths_total_2019' => $case['deaths_total_2019'],
                    'deaths_total_2020' => $case['deaths_total_2020'],
                    'epidemiological_week_2019' => $case['epidemiological_week_2019'],
                    'epidemiological_week_2020' => $case['epidemiological_week_2020'],
                    'new_deaths_covid19' => $case['new_deaths_covid19'],
                    'new_deaths_indeterminate_2019' => $case['new_deaths_indeterminate_2019'],
                    'new_deaths_indeterminate_2020' => $case['new_deaths_indeterminate_2020'],
                    'new_deaths_others_2019' => $case['new_deaths_others_2019'],
                    'new_deaths_others_2020' => $case['new_deaths_others_2020'],
                    'new_deaths_pneumonia_2019' => $case['new_deaths_pneumonia_2019'],
                    'new_deaths_pneumonia_2020' => $case['new_deaths_pneumonia_2020'],
                    'new_deaths_respiratory_failure_2019' => $case['new_deaths_respiratory_failure_2019'],
                    'new_deaths_respiratory_failure_2020' => $case['new_deaths_respiratory_failure_2020'],
                    'new_deaths_sars_2019' => $case['new_deaths_sars_2019'],
                    'new_deaths_sars_2020' => $case['new_deaths_sars_2020'],
                    'new_deaths_septicemia_2019' => $case['new_deaths_septicemia_2019'],
                    'new_deaths_septicemia_2020' => $case['new_deaths_septicemia_2020'],
                    'new_deaths_total_2019' => $case['new_deaths_total_2019'],
                    'new_deaths_total_2020' => $case['new_deaths_total_2020'],
                ]);

                $bar3->advance();
            });

            $bar3->finish();
            $this->output->success('Importação Concluida!');
        }
    }
}
