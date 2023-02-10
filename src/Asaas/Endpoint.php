<?php

namespace Asaas\Http;

class Endpoint
{

    public const CUSTOMER = "customers";
    public const CUSTOMER_GET = "customers/:id";
    public const CUSTOMER_CHANGE = "customers/:id";
    public const CUSTOMER_RESTORE = "customers/:id/restore";

    public const CUSTOMER_NOTIFICATIONS = "customers/:id/notifications";
    public const NOTIFICATIONS_UPDATE = "customers/:id/notifications";
    public const NOTIFICATIONS_UPDATE_BATCH = "notifications/batch";

    public const PAYMENTS = "payments";
    public const PAYMENTS_GET = "payments/:id";
    public const PAYMENTS_CHANGE = "payments/:id";
    public const PAYMENTS_RESTORE = "payments/:id/restore";
    public const PAYMENTS_REFUND = "payments/:id/refund";
    public const PAYMENTS_PAY_WITH_CREDIT_CARD = "payments/:id/payWithCreditCard";
    public const PAYMENTS_IDENTIFICATION_FIELD = "payments/:id/identificationField";
    public const PAYMENTS_PIX = "payments/:id/pixQrCode";
    public const PAYMENTS_RECEIVE_IN_CASH = "payments/:id/receiveInCash";
    public const PAYMENTS_UNDO_RECEIVE_IN_CASH = "payments/:id/undoReceivedInCash";

    public const INSTALLMENTS = "installments";
    public const INSTALLMENTS_GET = "installments/:id";
    public const INSTALLMENTS_CHANGE = "installments/:id";
    public const INSTALLMENTS_REFUND = "installments/:id/refund";

    public const SUBSCRIPTION = "subscriptions";
    public const SUBSCRIPTION_GET = "subscriptions/:id";
    public const SUBSCRIPTION_CHANGE = "subscriptions/:id";
    public const SUBSCRIPTION_BILLINGS = "subscriptions/:id/payments";
    public const SUBSCRIPTION_INVOICES = "subscriptions/:id/invoices";
    public const SUBSCRIPTION_INVOICES_SETTINGS = "subscriptions/:id/invoiceSettings";

    public const PAYMENT_LINK = "paymentLinks";
    public const PAYMENT_LINK_GET = "paymentLinks/:id";
    public const PAYMENT_LINK_CHANGE = "paymentLinks/:id";
    public const PAYMENT_LINK_RESTORE = "paymentLinks/:id/restore";
    public const PAYMENT_LINK_IMAGES = "paymentLinks/:id/images";
    public const PAYMENT_LINK_IMAGE_GET = "paymentLinks/:id/images/:imageId";
    public const PAYMENT_LINK_IMAGE_SET_AS_MAIN = "paymentLinks/:id/images/:imageId/setAsMain";

    public const TOKENIZE_CREDIT_CARD = "creditCard/tokenize";

    public const TRANSFERS = "transfers";
    public const TRANSFERS_GET = "transfers/:id";
    public const TRANSFERS_PIX_GET = "pix/transactions";
    public const TRANSFERS_PIX_SCHEDULED_CANCEL = "pix/transactions/:id/cancel";

    public const ANTICIPATIONS = "anticipations";
    public const ANTICIPATIONS_GET = "anticipations/:id";
    public const ANTICIPATIONS_SIMULATE = "anticipations/simulate";

    public const DUNNINGS = "paymentDunnings";
    public const DUNNINGS_GET = "paymentDunnings/:id";
    public const DUNNINGS_SIMULATE = "paymentDunnings/simulate";
    public const DUNNINGS_HISTORY = "paymentDunnings/:id/history";
    public const DUNNINGS_PAYMENTS = "paymentDunnings/:id/partialPayments";
    public const DUNNINGS_AVAILABLE = "paymentDunnings/paymentsAvailableForDunning";
    public const DUNNINGS_DOCUMENTS = "paymentDunnings/:id/documents";
    public const DUNNINGS_CANCEL = "paymentDunnings/:id/cancel";

    public const BILL = "bill";
    public const BILL_GET = "bill/:id";
    public const BILL_SIMULATE = "bill/simulate";
    public const BILL_CANCEL = "bill/:id/cancel";

    public const PHONE_RECHARGE = "mobilePhoneRecharges";
    public const PHONE_RECHARGE_GET = "mobilePhoneRecharges/:id";
    public const PHONE_RECHARGE_CANCEL = "mobilePhoneRecharges/:id/cancel";
    public const PHONE_RECHARGE_PROVIDER = "mobilePhoneRecharges/:phoneNumber/provider";

    public const CREDIT_BUREAU = "creditBureauReport";
    public const CREDIT_BUREAU_GET = "creditBureauReport/:id";

    public const BANKING_STATEMENT = "financialTransactions";

    public const BALANCE = "finance/balance";
    public const BALANCE_STATISTICS = "finance/payment/statistics";
    public const BALANCE_SPLIT_STATISTICS = "finance/split/statistics";

    public const COMMERCIAL_INFO = "myAccount/commercialInfo";
    public const COMMERCIAL_INFO_UPDATE = "myAccount/commercialInfo";

    public const CHECKOUT_CONFIG = "myAccount/paymentCheckoutConfig";
    public const CHECKOUT_CONFIG_GET = "myAccount/paymentCheckoutConfig";

    public const ACCOUNT_NUMBER = "myAccount/accountNumber";
    public const WALLET_ID = "wallets";

    public const INVOICES = "invoices";
    public const INVOICES_GET = "invoices/:id";
    public const INVOICES_UPDATE = "invoices/:id";
    public const INVOICES_CREATE = "invoices/:id/authorize";
    public const INVOICES_CANCEL = "invoices/:id/cancel";
    public const INVOICES_MUNICIPAL_SERVICES = "invoices/municipalServices";

    public const FICALS_INFO = "customerFiscalInfo";
    public const FISCAL_INFO_CREATE = "customerFiscalInfo";
    public const FISCAL_MUNICIPAL_INFO = "customerFiscalInfo/municipalOptions";

    public const PIX_KEYS = "pix/addressKeys";
    public const PIX_KEY_CREATE = "pix/addressKeys";
    public const PIX_KEY_GET = "pix/addressKeys/:id";
    public const PIX_KEY_REMOVE = "pix/addressKeys/:id";

    public const QRCODE_STATIC_CREATE = "pix/qrCodes/static";
    public const QRCODE_DECODE = "pix/qrCodes/decode";
    public const QRCODE_PAY = "pix/qrCodes/pay";

    public const WEBHOOK_PAYMENTS = "webhook";
    public const WEBHOOK_INVOICES = "webhook/invoice";
    public const WEBHOOK_TRANSFER = "webhook/transfer";
    public const WEBHOOK_BILL = "webhook/bill";
    public const WEBHOOK_ANTICIPATION = "webhook/anticipation";
    public const WEBHOOK_PHONE_RECHARGE = "webhook/mobilePhoneRecharge";

    public const SUB_ACCOUNT = "accounts";

    public const REGEX = '/:([^\/]*)/';

    public const MAJOR_PARAMETERS = [];

    protected $endpoint;

    protected $args = [];

    protected $vars = [];

    protected $query = [];

    public function __construct(string $endpoint)
    {
        $this->endpoint = $endpoint;

        if (preg_match_all(self::REGEX, $endpoint, $vars)) {
            $this->vars = $vars[1] ?? [];
        }
    }

    public function bindArgs(...$args): self
    {
        for ($i = 0; $i < count($this->vars) && $i < count($args); $i++) {
            $this->args[$this->vars[$i]] = $args[$i];
        }

        return $this;
    }

    public function bindAssoc(array $args): self
    {
        $this->args = array_merge($this->args, $args);

        return $this;
    }

    public function addQuery(string $key, $value): void
    {
        if (! is_bool($value)) {
            $value = (string) $value;
        }

        $this->query[$key] = $value;
    }

    public function toAbsoluteEndpoint(bool $onlyMajorParameters = false): string
    {
        $endpoint = $this->endpoint;

        foreach ($this->vars as $var) {
            if (! isset($this->args[$var]) || ($onlyMajorParameters && ! $this->isMajorParameter($var))) {
                continue;
            }

            $endpoint = str_replace(":{$var}", $this->args[$var], $endpoint);
        }

        if (! $onlyMajorParameters && count($this->query) > 0) {
            $endpoint .= '?'.http_build_query($this->query);
        }

        return $endpoint;
    }

    public function __toString(): string
    {
        return $this->toAbsoluteEndpoint();
    }

    public static function bind(string $endpoint, ...$args)
    {
        $endpoint = new Endpoint($endpoint);
        $endpoint->bindArgs(...$args);

        return $endpoint;
    }

    private static function isMajorParameter(string $param): bool
    {
        return in_array($param, self::MAJOR_PARAMETERS);
    }
}
