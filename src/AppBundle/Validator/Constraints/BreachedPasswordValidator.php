<?php

namespace AppBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Translation\TranslatorInterface;
use GuzzleHttp\Client as GuzzleClient;

class BreachedPasswordValidator extends ConstraintValidator
{
    private $translatorInterface;

    public function __construct(TranslatorInterface $translatorInterface)
    {
        $this->translatorInterface = $translatorInterface;
    }

    /**
     * Calls haveibeenpwned API (see https://haveibeenpwned.com/API/v2#SearchingPwnedPasswordsByRange) to check if
     * password has been compromised in a data breach.
     *
     * How it works :
     *      - user password is hashed with SHA-1
     *      - the first 5 characters of the hash (prefix) are sent to the API
     *      - API returns SHA-1 hashes "suffixes" of exposed passwords beginning with the same 5 characters
     *      - API suffixes are compared to user password hash suffix with strpos()
     *      - constraint violation is added if a match is found
     *
     * @param mixed $plainPassword
     * @param Constraint $constraint
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function validate($plainPassword, Constraint $constraint)
    {
        $plainPasswordSHA1 = strtoupper(sha1($plainPassword));
        $plainPasswordSHA1Prefix = substr($plainPasswordSHA1, 0, 5);
        $plainPasswordSHA1Suffix = substr($plainPasswordSHA1, 5);

        /*
         * Try catch to avoid Guzzle exceptions (e.g. if the API is unreachable Guzzle will throw 500 ConnectException,
         * without try catch it will crash the whole request and break the associated form)
         */
        try {
            $guzzleClient = new GuzzleClient();
            $guzzleRequest = $guzzleClient->request(
                'GET',
                'https://api.pwnedpasswords.com/range/' . $plainPasswordSHA1Prefix
            );
            $breachedPasswordsSuffixes = $guzzleRequest->getBody()->getContents();
        } catch (\Exception $e) {
            $breachedPasswordsSuffixes = '';
        }

        // Constraint violation if hashes match (strpos returns an integer if there is a match and false otherwise)
        if (is_int(strpos($breachedPasswordsSuffixes, $plainPasswordSHA1Suffix))) {
            $constraint->message = $this->translatorInterface->trans('form_errors.breached_password');
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}
