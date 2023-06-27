<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class NewsController extends Controller
{
    /**
     * Get news articles from multiple APIs
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function getNews(Request $request)
    {
        return $this->processArticles($request);
    }

    /**
     * Search news articles from multiple APIs based on a search term
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function searchNews(Request $request)
    {
        return $this->processArticles($request);
    }

    /**
     * Get news articles from multiple APIs based on a category
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function newsCategory(Request $request)
    {
        return $this->processArticles($request);
    }

    /**
     * Process news articles by fetching, formatting, and returning the response
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    private function processArticles(Request $request)
    {
        try {
            $articles = $this->fetchArticles($request);

            $formattedArticles = $this->formatArticles($articles);

            // Return a JSON response with the formatted articles
            return response()->json([
                'status' => 'ok',
                'message' => 'Success',
                'page' => $request->query('page'),
                'data' => $formattedArticles
            ]);
        } catch (\Exception $e) {
            // Log or handle the error as needed
            Log::error('Error occurred while formatting articles: ' . $e->getMessage());

            // Return an error response
            return response()->json(['error' => 'An error occurred while retrieving news articles.'], 500);
        }
    }

    /**
     * Fetch news articles from multiple APIs based on the request
     *
     * @param Request $request
     * @return array
     */
    private function fetchArticles(Request $request)
    {
        // Configuration for different news API clients
        $apiClients = [

            // The News API
            // 'The News API' => [
            //     'url' => 'https://newsapi.org/v2/everything',
            //     'parameters' => [
            //         'q' => $request->query('q') ?? '*',
            //         'pageSize' => 10,
            //         'page' => $request->query('page'),
            //         'apiKey' => $this->getApiKey('news_api_key'),
            //     ],
            //     'responseKey' => 'articles',
            // ], // Note: The News API is limited to 10 pages of results

            // New York Times API
            // 'The New York Times' => [
            //     'url' => 'https://api.nytimes.com/svc/search/v2/articlesearch.json',
            //     'parameters' => [
            //         'q' => $request->query('q'),
            //         'pageSize' => 10,
            //         'page' => $request->query('page'),
            //         'api-key' => $this->getApiKey('ny_times_api_key'),
            //     ],
            //     'responseKey' => 'response.docs',
            // ],

            // The Guardian API
            'The Guardian' => [
                'url' => 'https://content.guardianapis.com/search',
                'parameters' => [
                    'q' => $request->query('q'),
                    'pageSize' => 10,
                    'page' => $request->query('page'),
                    'api-key' => $this->getApiKey('guardian_api_key'),
                ],
                'responseKey' => 'response.results',
            ],
        ];

        $articles = [];


        // Check if the page parameter is less than or equal to 10
        $page = $request->query('page');
        $includeNewsAPI = true;
        if ($page && $page > 10) {
            $includeNewsAPI = false;
        }

        // Fetch articles from each API client
        foreach ($apiClients as $source => $client) {
            // Exclude NewsAPI if page > 10
            if ($source === 'The News API' && !$includeNewsAPI) {
                continue;
            }

            $response = Http::get($client['url'], $client['parameters']);
            $articles[$source] = $response->json($client['responseKey']);
        }

        return $articles;
    }

    /**
     * Format the fetched articles into a common structure
     *
     * @param array $articles
     * @return array
     */
    private function formatArticles($articles)
    {
        $formattedArticles = [];

        // Extract relevant data from each article and store it in a common format
        foreach ($articles as $source => $rawArticles) {
            foreach ($rawArticles as $article) {
                $data = $this->extractData($article, $source);

                // Generate a UUID for each article
                $data['id'] = Str::uuid()->toString();

                // 
                $formattedArticles[] = $data;
            }
        }

        // Sort the articles by date in descending order
        usort($formattedArticles, function ($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });

        return $formattedArticles;
    }

    /**
     * Extract relevant data from the article based on the API source
     *
     * @param array $article
     * @param string $api
     * @return array
     */
    private function extractData($article, $api)
    {
        switch ($api) {
            case 'The News API':
                return [
                    'date' => $article['publishedAt'],
                    'author' => $article['author'],
                    'title' => $article['title'],
                    'category' => 'N/A',
                    'web_url' => $article['url'],
                    'source' => $api,
                ];

            case 'The New York Times':
                return [
                    'date' => $article['pub_date'],
                    'author' => $article['byline']['original'],
                    'title' => $article['abstract'],
                    'category' => $article['section_name'],
                    'web_url' => $article['web_url'],
                    'source' => $api,
                ];

            case 'The Guardian':
                return [
                    'date' => $article['webPublicationDate'],
                    'author' => 'The Guardian',
                    'title' => $article['webTitle'],
                    'category' => $article['sectionName'] ?? 'N/A',
                    'web_url' => $article['webUrl'],
                    'source' => $api,
                ];
        }

        return [];
    }

    /**
     * Get the API key from the configuration based on the given key
     *
     * @param string $configKey
     * @return mixed
     */
    private function getApiKey($configKey)
    {
        return Config::get('app.' . $configKey);
    }
}
