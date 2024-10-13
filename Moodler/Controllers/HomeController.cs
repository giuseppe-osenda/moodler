using System.ClientModel;
using System.ClientModel.Primitives;
using System.Diagnostics;
using System.Globalization;
using System.Net;
using System.Security.Cryptography;
using System.Text;
using System.Text.Json;
using System.Text.RegularExpressions;
using Microsoft.AspNetCore.Mvc;
using Moodler.Helpers;
using Moodler.Models;
using OpenAI;
using OpenAI.Chat;
using SpotifyAPI.Web;

namespace Moodler.Controllers;

public class HomeController(
    ILogger<HomeController> logger,
    IConfiguration configuration,
    CategoriesHelper categoriesHelper) : BaseController
{
    //private readonly string _chatGptApiKey = Decrypt(configuration.GetSection("Api:OpenAiKeyLocal").Value ?? "");
    private readonly string _chatGptApiKey = Decrypt(configuration.GetSection("Api:OpenAiKey").Value ?? "");
    private static readonly string apiUrl = "https://api.openai.com/v1/chat/completions";
    private static readonly string proxyAddress = "http://winproxy.server.lan:3128";  // Your proxy address
    
    // Create byte array for additional entropy when using Protect method.
    static byte[] s_additionalEntropy = { 9, 8, 7, 6, 5 };

    [HttpPost]
    public IActionResult Encrypt(string clearText)
    {
        byte[] clearBytes = Encoding.UTF8.GetBytes(clearText);
        byte[] encryptedBytes =
            ProtectedData.Protect(clearBytes, s_additionalEntropy, DataProtectionScope.LocalMachine);
        ViewBag.Key = Convert.ToBase64String(encryptedBytes);
        return View("Index");
    }

    public static string Decrypt(string cipherText)
    {
        try
        {
            byte[] cipherBytes = Convert.FromBase64String(cipherText);
            byte[] decryptedBytes =
                ProtectedData.Unprotect(cipherBytes, s_additionalEntropy, DataProtectionScope.LocalMachine);
            return Encoding.UTF8.GetString(decryptedBytes);
        }
        catch (Exception e)
        {
            throw new ApplicationException("Failed to decrypt data", e);
        }
    }

    /*
    public async Task<IActionResult> Index()
    {
        var prompt = "Tell me a joke";
        var response = await GetCompletionAsync(prompt, _chatGptApiKey, "http://winproxy.server.lan:3128");
        ViewBag.Response = response;
        return View();
    }
    */

    public IActionResult Index()
    {
        return View();
    }
    
    [HttpPost]
    public IActionResult GetCategories(string request)
    {
        try
        {
            TempData.Clear();
            
            // Set up the proxy
            var proxy = new WebProxy(proxyAddress, false)
            {
                UseDefaultCredentials = true  // If the proxy requires authentication with the current user's credentials
            };

            // Set up the HttpClientHandler with the proxy
            var httpClientHandler = new HttpClientHandler
            {
                Proxy = proxy,
                UseProxy = true
            };
            
            var httpClient = new HttpClient(httpClientHandler);
            
            OpenAIClientOptions options = new()
            {
                Transport = new HttpClientPipelineTransport(httpClient),
            };

            var apiKeyCredential = new ApiKeyCredential(_chatGptApiKey);
            
            
            ChatClient client = new(model: "gpt-4o-mini", apiKeyCredential, options);


            var formattedRequest =
                $"Give me in english one time of the day, one mood, one relationship and one musical taste which are related to : \\\"{request}\\\" separated by comma";

            ChatCompletion chatCompletion = client.CompleteChat(formattedRequest);
            var completion = chatCompletion.Content[0].Text;
            ViewBag.Completion = completion;

        
            if (string.IsNullOrEmpty(completion))
            {
                TempData["error"] = "An error occured while trying to retrieve your categories, please try again";
                return View("Index");
            }

            var categories = completion.Trim('.').Split(',').ToList();
            var homeViewModel = new HomeViewModel();

            var dayTimes = categoriesHelper.GetDayTimes(categories[0]);
            var moods = categoriesHelper.GetMoods(categories[1]);
            var relationships = categoriesHelper.GetRelationships(categories[2]);
            var musicalTastes = categoriesHelper.GetMusicalTastes(categories[3]);


            foreach (var dayTime in dayTimes)
            {
                homeViewModel.DayTimesDictionary.Add(dayTime,
                    dayTime.Equals(categories.ElementAt(0), StringComparison.OrdinalIgnoreCase));
            }

            foreach (var mood in moods)
            {
                homeViewModel.MoodsDictionary.Add(mood,
                    mood.Equals(categories.ElementAt(1), StringComparison.OrdinalIgnoreCase));
            }

            foreach (var relationship in relationships)
            {
                homeViewModel.RelationshipsDictionary.Add(relationship,
                    relationship.Equals(categories.ElementAt(2), StringComparison.OrdinalIgnoreCase));
            }

            foreach (var musicalTaste in musicalTastes)
            {
                homeViewModel.MusicalTastesDictionary.Add(musicalTaste,
                    musicalTaste.Equals(categories.ElementAt(3), StringComparison.OrdinalIgnoreCase));
            }


            return View("Index", homeViewModel);
        }
        catch (Exception e)
        {
            ViewBag.Error = e.Message;
        }

        return View("Index");
    }

    private static string ExtractJson(string? input)
    {
        var match = Regex.Match(input, @"\{.*\}", RegexOptions.Singleline);

        return match.Success ? match.Value : string.Empty;
    }

    private static List<string> GetTrackNamesFromJson(string json)
    {
        var trackNames = new List<string>();
        var jsonDocument = JsonDocument.Parse(json);
        var tracks = jsonDocument.RootElement.GetProperty("tracks").EnumerateArray();

        foreach (var track in tracks)
        {
            trackNames.Add(track.GetProperty("title").GetString() ?? string.Empty);
        }

        return trackNames;
    }

    private List<string> GetTracksName(string completions)
    {
        ChatClient client = new(model: "gpt-4o-mini", _chatGptApiKey);

        var formattedRequest =
            $" return me a json object with a list of 30 song names with its artist related to these categories: \\\"{completions}\\\".  json must have this structure {{\"tracks\": [{{\"title\" : \"value\"}}]}}";

        ChatCompletion result = client.CompleteChat(formattedRequest);

        var content = result.Content[0].Text;


        var json = ExtractJson(content);

        var trackNames = GetTrackNamesFromJson(json);

        return trackNames;
    }

    [HttpGet]
    public async Task<IActionResult> CreatePlaylist(string completions, string userInput)
    {
        await HttpContext.Session.LoadAsync();
        var spotifyClient = new SpotifyClient("");
        var accessToken = HttpContext.Session.GetString("access_token");
        var errorResult = new JsonResult("error");
        var successResult = new JsonResult("success");
        TempData.Clear();

        if (string.IsNullOrEmpty(accessToken)) //if there is no access_token user need to log in
        {
            return new JsonResult(new { status = "error", message = "Please log in" });
        }

        var refreshToken = HttpContext.Session.GetString("refresh_token");

        if (IsTokenExpired() && !string.IsNullOrEmpty(refreshToken)) //refresh token if expire
        {
            var refreshedToken = await RefreshToken(refreshToken);
            HttpContext.Session.SetString("refresh_token", refreshedToken.RefreshToken);
            HttpContext.Session.SetString("access_token", refreshedToken.AccessToken);
            HttpContext.Session.SetString("access_token_creation_date",
                refreshedToken.CreatedAt.ToString(CultureInfo.CurrentCulture));
            spotifyClient = new SpotifyClient(refreshedToken.AccessToken);
        }

        var config = DefaultConfig.WithToken(accessToken);
        spotifyClient = new SpotifyClient(config);

        try
        {
            var user = await spotifyClient.UserProfile.Current();
            var playlist =
                await spotifyClient.Playlists.Create(user.Id, new PlaylistCreateRequest(userInput));

            var tracksName = GetTracksName(completions);
            var playlistItemModel = new PlaylistAddItemsRequest(new List<string>());

            foreach (var trackName in tracksName)
            {
                var searchRequest = new SearchRequest(SearchRequest.Types.Track, trackName);
                var searchResponse = await spotifyClient.Search.Item(searchRequest);
                var trackId = searchResponse.Tracks.Items?.FirstOrDefault()?.Id;

                if (trackId == null) continue;
                var trackUri = $"spotify:track:{trackId}";
                playlistItemModel.Uris.Add(trackUri);
            }


            if (playlistItemModel.Uris.Count == 0) return errorResult;

            await spotifyClient.Playlists.AddItems(playlist.Id, playlistItemModel);


            return successResult;
        }
        catch (Exception e)
        {
            TempData["error"] = "An error occured please try again";
        }


        return errorResult;
    }

    public RedirectResult Login()
    {
        var (verifier, challenge) = PKCEUtil.GenerateCodes();

        HttpContext.Session.SetString("verifier", verifier);

        var loginRequest = new LoginRequest(
            new Uri("https://moodler.app/home/callback"),
            "f4cd70cc16604aaf99eae2801a16a949",
            LoginRequest.ResponseType.Code
        )
        {
            CodeChallengeMethod = "S256",
            CodeChallenge = challenge,
            Scope =
            [
                Scopes.PlaylistReadPrivate, Scopes.PlaylistReadCollaborative, Scopes.PlaylistModifyPublic,
                Scopes.PlaylistModifyPrivate
            ]
        };

        var uri = loginRequest
            .ToUri(); // Redirect user to uri via your favorite web-server or open a local browser window 

        return Redirect(uri.ToString());
    }

    public async Task<RedirectToActionResult> Callback(string code)
    {
        TempData.Clear();
        var verifier = HttpContext.Session.GetString("verifier");

        if (string.IsNullOrEmpty(verifier)) //no verifier error handling
        {
            TempData["error"] = "An error occured, please try again";
            return RedirectToAction("Index", "Home");
        }


        try
        {
            // Note that we use the verifier calculated above!
            var initialResponse = await new OAuthClient().RequestToken(
                new PKCETokenRequest("f4cd70cc16604aaf99eae2801a16a949", code,
                    new Uri("https://moodler.app/home/callback"), verifier)
            );

            HttpContext.Session.SetString("access_token", initialResponse.AccessToken);
            SetCreationDate(initialResponse.CreatedAt);
            HttpContext.Session.SetString("refresh_token", initialResponse.RefreshToken);
        }
        catch (Exception e)
        {
            TempData["error"] = e.Message;
        }

        return
            RedirectToAction("Index", "Home"); //once user auth is successful 
    }

    private void SetCreationDate(DateTime initialResponseCreatedAt)
    {
        var createdAt = initialResponseCreatedAt.ToString(CultureInfo.InvariantCulture);
        var createdAtUtc = DateTime.Parse(createdAt, null, DateTimeStyles.AdjustToUniversal);

        // Ottieni il fuso orario locale
        var localTimeZone = TimeZoneInfo.Local;

        // Converte createdAt al fuso orario locale
        DateTime createdAtLocal = TimeZoneInfo.ConvertTimeFromUtc(createdAtUtc, localTimeZone);

        HttpContext.Session.SetString("access_token_creation_date",
            createdAtLocal.ToString(CultureInfo.InvariantCulture));
    }

    private bool IsTokenExpired()
    {
        var sessionTokenCreationDate = HttpContext.Session.GetString("access_token_creation_date");

        if (string.IsNullOrEmpty(sessionTokenCreationDate))
            return true;

        var tokenCreationDate = DateTime.Parse(sessionTokenCreationDate);

        return (DateTime.Now - tokenCreationDate).TotalHours > 1;
    }

    private async Task<PKCETokenResponse> RefreshToken(string refreshToken)
    {
        var newResponse = await new OAuthClient().RequestToken(
            new PKCETokenRefreshRequest("f4cd70cc16604aaf99eae2801a16a949", refreshToken)
        );

        return newResponse;
    }


    public IActionResult Privacy()
    {
        return View();
    }

    [ResponseCache(Duration = 0, Location = ResponseCacheLocation.None, NoStore = true)]
    public IActionResult Error()
    {
        return View(new ErrorViewModel { RequestId = Activity.Current?.Id ?? HttpContext.TraceIdentifier });
    }

    public static async Task<string> GetCompletionAsync(string prompt, string chatGptApiKey, string proxyAddress)
    {
        // Set up the proxy
        var proxy = new WebProxy(proxyAddress, false)
        {
            UseDefaultCredentials = true  // If the proxy requires authentication with the current user's credentials
        };

        // Set up the HttpClientHandler with the proxy
        var httpClientHandler = new HttpClientHandler
        {
            Proxy = proxy,
            UseProxy = true
        };
        
        using var client = new HttpClient(httpClientHandler);
        client.DefaultRequestHeaders.Add("Authorization", $"Bearer {chatGptApiKey}");
        // Define the request body for the chat model
        var requestBody = new
        {
            model = "gpt-4o-mini",  // Change to "gpt-4" or "gpt-3.5-turbo" if needed
            messages = new[]
            {
                new { role = "system", content = "You are a helpful assistant." },
                new { role = "user", content = prompt }
            }
        };

        // Convert the request body to JSON format
        var jsonContent = new StringContent(Newtonsoft.Json.JsonConvert.SerializeObject(requestBody), Encoding.UTF8, "application/json");

        // Make the POST request to the OpenAI API
        var response = await client.PostAsync(apiUrl, jsonContent);

        if (response.IsSuccessStatusCode)
        {
            var result = await response.Content.ReadAsStringAsync();
            return result;  // This will return the raw JSON response from the API
        }
        else
        {
            var errorMessage = await response.Content.ReadAsStringAsync();  // Get error message from the response content
            return $"Error: {response.StatusCode} - {response.ReasonPhrase}: {errorMessage}";
        }
    }
}