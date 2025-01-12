<?php

namespace Mediashare\Entity;

use Mediashare\Entity\Website;
use Mediashare\Entity\WebPage;

class Url
{
    public $id;
    public $url;
    public $scheme;
    public $host;
    public $port;
    public $path;
    public $query;
    public $fragment;
    public $isCrawled;
    public $isExcluded;
    public $website;
    public $createDate;
    public $updateDate;
    public $webpage;

    public function __toString() {
        return $this->getUrl();
    }

    public function __construct(string $url = "http://marquand.pro") {
        $this->setUpdateDate();
        $this->setCrawled(false);
        $this->setExcluded(false);
        $this->setUrl($url);
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(string $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;
        // $this->setId(uniqid());
        $this->setId($url);
        // Parse url
        $this->setScheme(parse_url($url, PHP_URL_SCHEME));
        $this->setHost(parse_url($url, PHP_URL_HOST));
        $this->setPort(parse_url($url, PHP_URL_PORT));
        $this->setPath(parse_url($url, PHP_URL_PATH));
        $this->setQuery(parse_url($url, PHP_URL_QUERY));
        $this->setFragment(parse_url($url, PHP_URL_FRAGMENT));

        return $this;
    }

    public function getScheme(): ?string
    {
        return $this->scheme;
    }

    public function setScheme(?string $scheme): self
    {
        $this->scheme = $scheme;

        return $this;
    }

    public function getHost(): ?string
    {
        return $this->host;
    }

    public function setHost(?string $host): self
    {
        $this->host = $host;

        return $this;
    }

    public function getPort(): ?int
    {
        return $this->port;
    }

    public function setPort(?int $port): self
    {
        $this->port = $port;

        return $this;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(?string $path): self
    {
        if (!$path) {$path = "/";}
        $this->path = $path;

        return $this;
    }

    public function getQuery(): ?string
    {
        return $this->query;
    }

    public function setQuery(?string $query): self
    {
        $this->query = $query;

        return $this;
    }

    public function getFragment(): ?string
    {
        return $this->fragment;
    }

    public function setFragment(?string $fragment): self
    {
        $this->fragment = $fragment;

        return $this;
    }

    public function isCrawled(): ?bool
    {
        return $this->isCrawled;
    }

    public function setCrawled(bool $isCrawled): self
    {
        $this->isCrawled = $isCrawled;

        return $this;
    }

    public function isExcluded(): ?bool
    {
        return $this->isExcluded;
    }

    public function setExcluded(bool $isExcluded): self
    {
        $this->isExcluded = $isExcluded;

        return $this;
    }

    public function getWebsite(): ?Website
    {
        return $this->website;
    }

    public function setWebsite(?Website $website): self
    {
        $this->website = $website;

        return $this;
    }

    public function getCreateDate(): ?\DateTime
    {
        return $this->createDate;
    }

    public function setCreateDate(): self
    {
        $this->createDate = new \DateTime();

        return $this;
    }

    public function getUpdateDate(): ?\DateTime
    {
        return $this->updateDate;
    }

    public function setUpdateDate(): self
    {
        if (!$this->createDate) {
            $this->setCreateDate();
        }
        $this->updateDate = new \DateTime();

        return $this;
    }

    public function getWebpage(): ?WebPage
    {
        return $this->webpage;
    }

    public function setWebpage(?WebPage $webpage): self
    {
        $this->webpage = $webpage;

        return $this;
    }
    
    public function getSources(Website $website): ?array
    {
        $sources = [];
        $urls = $website->getUrls();
        foreach ($urls as $url) {
            if ($url->isCrawled()) {
                $links = $url->getWebPage()->getLinks(); // Internal links
                foreach ($links as $link) {
                    if ($link == $this->getUrl() || 
                        rtrim(parse_url($link, PHP_URL_PATH), '/') === rtrim(parse_url($this->getUrl(), PHP_URL_PATH), '/')) {
                        $sources[$url->getUrl()] = $url->getUrl();
                    }
                }
            }
        }
        $sources = array_values($sources); // Reset array keys
        return $sources;
    }

    public function checkUrl(WebPage $webPage) {
		$website = $webPage->getUrl()->getWebsite();
		$config = $website->getConfig();
        $url = $this->getUrl();
		if ($url == "/") {
            $url = rtrim($website->getScheme().'://'.$website->getDomain(),"/").$url;
        } elseif ($url[0] == "#") {
            $url = rtrim($webPage->getUrl()->getUrl()  ,"/")."/".$url;
        } else {
            $isUrl = filter_var($url, FILTER_VALIDATE_URL);
            if (!$isUrl && ($url[0] === "/" && $url[1] !== "/")) {
                $url = rtrim($website->getScheme().'://'.$website->getDomain(),"/").$url;
                $isUrl = filter_var($url, FILTER_VALIDATE_URL);
            }
			if (!$isUrl && strpos($url, $website->getScheme().'://'.$website->getDomain()) === false) {
                $url = rtrim($webPage->getUrl()->getUrl(),"/")."/".$url;
				$isUrl = filter_var($url, FILTER_VALIDATE_URL);
			}
            if (!$isUrl) {
                $url = rtrim($website->getScheme().'://'.$website->getDomain(),"/")."/".$url;
                $isUrl = filter_var($url, FILTER_VALIDATE_URL);
            }
		}



        // Exceptions
        $isUrl = filter_var($url, FILTER_VALIDATE_URL);
        if (!$isUrl) {
            $this->setExcluded(true);
        }

        $this->__construct($url);

		if ($this->getHost() !== $website->getDomain()) {
            $this->setExcluded(true);
        }
        
		if ($this->getHost() !== $website->getDomain()) {
			$this->setExcluded(true);
		}
		foreach ($config->getExceptions() as $value) {
			if (strpos($url, $value) !== false) {
				$this->setExcluded(true);
			}
		}
		foreach ($config->getRequires() as $value) {
			if (strpos($url, $value) === false) {
				$this->setExcluded(true);
			}
		}

		if (!$this->isExcluded()) {
			$webPage->addLinks($url);
			return $url;
		} else {
			$webPage->addExternalLinks($url);
			return false;
		}
	}
}
