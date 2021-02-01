<?php

namespace DiegoSouza\Zimbra;

use Illuminate\Log\LogManager;
use Illuminate\Support\Traits\ForwardsCalls;
use Zimbra\Admin\AdminFactory;
use Zimbra\Admin\Struct\DistributionListSelector;
use Zimbra\Admin\Struct\DomainSelector;
use Zimbra\Enum\AccountBy;
use Zimbra\Enum\DistributionListBy;
use Zimbra\Enum\DomainBy;
use Zimbra\Enum\GranteeType;
use Zimbra\Enum\Operation;
use Zimbra\Enum\DistributionListGranteeBy;
use Zimbra\Struct\AccountSelector;
use Zimbra\Struct\KeyValuePair;
use Zimbra\Account\Struct\DistributionListRightSpec;
use Zimbra\Account\Struct\DistributionListGranteeSelector;
use Zimbra\Account\Request\DistributionListAction;
use Zimbra\Account\Struct\DistributionListAction as DLAction;

class ZimbraApiClient
{
    use ForwardsCalls;

    protected $api;
    protected $logger;
    protected $domain;

    public function __construct(string $host, string $emailDomain, string $user, string $password, LogManager $logger)
    {
        $this->api = AdminFactory::instance("https://{$host}:7071/service/admin/soap");
        $this->api->auth($user, $password);

        $this->domain = $emailDomain;
        $this->logger = $logger;

        $this->api->getClient()->on('before.request', function ($request) {
            $this->logger->debug("SOAP REQUEST: {$request}");
        });

        $this->api->getClient()->on('after.request', function ($response) {
            $this->logger->debug("SOAP RESPONSE: {$response}");
        });
    }

    public function createDistributionList(string $distListName)
    {
        $dynamic = false;
        $attr = $this->createKeyPair();

        return $this->api->createDistributionList("{$distListName}@{$this->domain}", $dynamic, [$attr]);
    }

    public function deleteDistributionList(string $distListId)
    {
        return $this->api->deleteDistributionList($distListId);
    }

    public function getAllDistributionLists()
    {
        $domainSelector = new DomainSelector(DomainBy::NAME(), $this->domain);
        return $this->api->getAllDistributionLists($domainSelector);
    }

    public function addDistributionListMember(string $distListId, array $users)
    {
        return $this->api->addDistributionListMember($distListId, $users);
    }

    public function getDistributionListById(string $distListId)
    {
        $limit = 0;
        $offset = 0;
        $sortAscending = true;
        $attr = $this->createKeyPair();

        $dl = new DistributionListSelector(DistributionListBy::ID(), $distListId);

        return $this->api->getDistributionList($dl, $limit, $offset, $sortAscending, [$attr]);
    }

    public function getDistributionListByName(string $distListName)
    {
        $limit = 0;
        $offset = 0;
        $sortAscending = true;
        $attr = $this->createKeyPair();

        $dl = new DistributionListSelector(DistributionListBy::NAME(), $distListName);

        return $this->api->getDistributionList($dl, $limit, $offset, $sortAscending, [$attr]);
    }

    public function GrantSendAs(string $distListName, string $emailAccountName)
    {
        $grantee = new DistributionListGranteeSelector(GranteeType::USR(), DistributionListGranteeBy::NAME(), $emailAccountName);
        $right = new DistributionListRightSpec("sendAsDistList", [$grantee]);
        $dl = new DistributionListSelector(DistributionListBy::Name(), $distListName);
        $a = $this->createKeyPair();
        $action = new DLAction(Operation::GRANT_RIGHTS(), null, null, [], [], [$right], [$a]);
        $attr = $this->createKeyPair();

        return $this->api->distributionListAction($dl, $action, [$attr]);
    }

    public function RevokeSendAs(string $distListName, string $emailAccountName)
    {
        $grantee = new DistributionListGranteeSelector(GranteeType::USR(), DistributionListGranteeBy::NAME(), $emailAccountName);
        $right = new DistributionListRightSpec("sendAsDistList", [$grantee]);
        $dl = new DistributionListSelector(DistributionListBy::Name(), $distListName);
        $a = $this->createKeyPair();
        $action = new DLAction(Operation::GRANT_RIGHTS(), null, null, [], [], [$right], [$a]);
        $attr = $this->createKeyPair();

        return $this->api->distributionListAction($dl, $action, [$attr]);
    }

    public function GrantSendOnBehalfTo(string $distListName, string $emailAccountName)
    {
        $grantee = new DistributionListGranteeSelector(GranteeType::USR(), DistributionListGranteeBy::NAME(), $emailAccountName);
        $right = new DistributionListRightSpec("sendOnBehalfOfDistList", [$grantee]);
        $dl = new DistributionListSelector(DistributionListBy::Name(), $distListName);
        $a = $this->createKeyPair();
        $action = new DLAction(Operation::GRANT_RIGHTS(), null, null, [], [], [$right], [$a]);
        $attr = $this->createKeyPair();

        return $this->api->distributionListAction($dl, $action, [$attr]);
    }

    public function RevokeSendOnBehalfTo(string $distListName, string $emailAccountName)
    {
        $grantee = new DistributionListGranteeSelector(GranteeType::USR(), DistributionListGranteeBy::NAME(), $emailAccountName);
        $right = new DistributionListRightSpec("sendOnBehalfOfDistList", [$grantee]);
        $dl = new DistributionListSelector(DistributionListBy::Name(), $distListName);
        $a = $this->createKeyPair();
        $action = new DLAction(Operation::REVOKE_RIGHTS(), null, null, [], [], [$right], [$a]);
        $attr = $this->createKeyPair();

        return $this->api->distributionListAction($dl, $action, [$attr]);
    }

    public function getDistributionListMembership(string $distListName)
    {
        $limit = 0;
        $offset = 0;

        $dl = new DistributionListSelector(DistributionListBy::Name(), $distListName);

        return $this->api->getDistributionListMembership($dl, $limit, $offset);
    }

    public function getAllAccounts() {
        $serverSelector = null;
        $domainSelector = new DomainSelector(DomainBy::NAME(), $this->domain);

        return $this->api->getAllAccounts($serverSelector, $domainSelector);
    }

    public function modifyCoS(string $emailAccountId, string $cosId)
    {
        $attr = $this->createKeyPair('zimbraCOSId', $cosId);
        return $this->api->modifyAccount($emailAccountId, [$attr]);
    }

    public function removeDistributionListMember(string $distListId, string $emailAccountName)
    {
        $dlms = [$emailAccountName];
        $accounts = [];

        return $this->api->removeDistributionListMember($distListId, $dlms, $accounts);
    }

    public function renameDistributionList(string $distListId, string $newName)
    {
        return $this->api->renameDistributionList($distListId, $newName);
    }

    public function getAccountById(string $emailAccountId)
    {
        $apllyCos = null;

        $attr = [
            'sn',
            'uid',
            'mail',
            'givenName',
            'zimbraMailQuota',
            'zimbraAccountStatus',
        ];

        $account = new AccountSelector(AccountBy::ID(), $emailAccountId);

        return $this->api->getAccount($account, $apllyCos, $attr)->account;
    }

    public function getAccountByName(string $emailAccountName)
    {
        $apllyCos = null;

        $attr = [
            'sn',
            'uid',
            'mail',
            'givenName',
            'zimbraMailQuota',
            'zimbraAccountStatus',
        ];

        $account = new AccountSelector(AccountBy::NAME(), $emailAccountName);

        return $this->api->getAccount($account, $apllyCos, $attr)->account;
    }

    public function getAccountMembershipByName(string $emailAccountName)
    {
        $account = new AccountSelector(AccountBy::NAME(), $emailAccountName);
        return $this->api->getAccountMembership($account);
    }

    public function getAccountMembershipbyId(string $emailAccountId)
    {
        $account = new AccountSelector(AccountBy::ID(), $emailAccountId);
        return $this->api->getAccountMembership($account);
    }

    public function getAccountInfoById(string $emailAccountId)
    {
        $account = new AccountSelector(AccountBy::ID(), $emailAccountId);
        return $this->api->getAccountInfo($account);
    }

    public function getAccountInfoByName($emailAccountName)
    {
        $account = new AccountSelector(AccountBy::NAME(), $emailAccountName);
        return $this->api->getAccountInfo($account);
    }

    public function getAllCos()
    {
        return $this->api->getAllCos()->cos;
    }

    public function getAllDomains()
    {
        return $this->api->getAllDomains()->domain;
    }

    public function api()
    {
        return $this->api;
    }

    public function __call($method, $parameters)
    {
        return $this->forwardCallTo($this->api, $method, $parameters);
    }

    private function createKeyPair($key = '', $value = '')
    {
        return new KeyValuePair($key, $value);
    }
}