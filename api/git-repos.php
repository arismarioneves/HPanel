<?php

declare(strict_types=1);

require __DIR__ . '/../lib/autoload.php';

use HPanel\{ApiError, Context, Http, Request, Validate};

Http::handle(['GET', 'POST'], static function (Request $req, Context $ctx): array {
    $orderId = Validate::orderId($req->get('orderId'));
    $site = $ctx->catalog->site($orderId, Validate::domain($req->get('domain')));
    $source = $ctx->source();
    $list = static fn(): array => ['repos' => $source->gitRepos($site['username'], $site['domain'], $orderId)];

    if ($req->method === 'GET') {
        $repoId = $req->get('repoId');
        if ($repoId === null || $repoId === '') {
            return $list();
        }
        return ['output' => $source->gitRepoOutput($site['username'], $site['domain'], $orderId, Validate::repoId($repoId))];
    }

    match ($req->get('action')) {
        'create' => $source->createGitRepo(
            $site['username'],
            $site['domain'],
            $orderId,
            Validate::repoUrl($req->get('repository')),
            Validate::branch($req->get('branch')),
            Validate::directory($req->get('directory')),
        ),
        'deploy' => $source->deployGitRepo($site['username'], $site['domain'], $orderId, Validate::repoId($req->get('repoId'))),
        'delete' => $source->deleteGitRepo($site['username'], $site['domain'], $orderId, Validate::repoId($req->get('repoId'))),
        default => throw ApiError::invalid('Ação inválida.'),
    };

    return $list();
});
