<?php

namespace MorningMedley\Application\Providers;

use Illuminate\Support\ServiceProvider;

class DebugInformationServiceProvider extends ServiceProvider
{
    public function boot()
    {
        \add_filter('debug_information', $this->debugInformationFilter(...));
    }

    public function debugInformationFilter(array $debugInfo): array
    {
        $fields = [
            [
                'label' => 'Version',
                'value' => app()->version(),
            ],
        ];

        /** @var \MorningMedley\Application\PackageManifest $packageManifest */
        $packageManifest = app(\MorningMedley\Application\PackageManifest::class);

        foreach (array_keys($packageManifest->manifest) as $packageName) {
            $version = "-";
            try {
                $version = \Composer\InstalledVersions::getVersion($packageName);
            } catch (\Throwable $exception) {
            }

            $fields[] = [
                'label' => $packageName,
                'value' => \apply_filters("morningmedley/debugInformation/{$packageName}",['version' => $version]),
            ];
        }

        $debugInfo['morningmedley'] = [
            'label' => "Morningmedley",
            'fields' => \apply_filters('morningmedley/debugInformation/Fields', $fields),
        ];

        return $debugInfo;
    }

}
