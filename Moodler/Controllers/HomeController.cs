using System.ClientModel;
using System.ClientModel.Primitives;
using System.Diagnostics;
using System.Globalization;
using System.Net;
using System.Net.Http.Headers;
using System.Security.Cryptography;
using System.Text;
using System.Text.Json;
using System.Text.RegularExpressions;
using Microsoft.AspNetCore.Mvc;
using Microsoft.Extensions.Hosting.Internal;
using Moodler.Helpers;
using Moodler.Models;
using Moodler.Services;
using OpenAI;
using OpenAI.Chat;
using SpotifyAPI.Web;

namespace Moodler.Controllers;

public class HomeController(
    ILogger<HomeController> logger,
    CategoriesHelper categoriesHelper,
    IOpenAiService openAiService,
    ISpotifyService spotifyService,
    IConfiguration configuration,
    IWebHostEnvironment env,
    ProxyHelper proxyHelper) : BaseController
{
    private readonly string _callbackUri = configuration.GetSection("Configurations:UriCallBack").Value ?? "";
        

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

            var client = openAiService.GetChatClient();
            
            var formattedRequest =
                $"Provide me in English one time of day, one mood, one relationship, and one specific musical genre (not a feeling or mood like 'nostalgic') that are related to the following situation: \"{request}\". Separate each item with a comma.\n";
            

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
            var title = track.GetProperty("title").GetString() ?? string.Empty;
            var artist = track.GetProperty("artist").GetString() ?? string.Empty;
            trackNames.Add($"{title} - {artist}");
        }

        return trackNames;
    }

    private List<string> GetTracksName(string[] completions)
    {
        var client = openAiService.GetChatClient();
        
        var formattedRequest =
            $"Restituiscimi un oggetto JSON contenente una lista di 20 brani con i rispettivi artisti. Ogni brano deve soddisfare necessariamente i seguenti criteri:\n\n- Il brano deve essere adatto al momento della giornata specificato in \"{completions[0]}\".\n- Il brano deve corrispondere all'umore descritto in \"{completions[1]}\".\n- Il brano deve riflettere la relazione specificata in \"{completions[2]}\".\n- Il brano deve appartenere alla categoria musicale \"{completions[3]}\".\n\nIl JSON deve avere la seguente struttura:\n{{\n  \"tracks\": [\n    {{\n      \"title\": \"nome brano\",\n      \"artist\": \"nome artista\"\n    }}\n  ]\n}}\n";
        

        ChatCompletion result = client.CompleteChat(formattedRequest);

        var content = result.Content[0].Text;


        var json = ExtractJson(content);

        var trackNames = GetTrackNamesFromJson(json);

        return trackNames;
    }

    [HttpGet]
    public async Task<IActionResult> CreatePlaylist(string[] completions, string userInput)
    {
        await HttpContext.Session.LoadAsync();
        SpotifyClient spotifyClient;
        var accessToken = HttpContext.Session.GetString("access_token");
        TempData.Clear();

        if (string.IsNullOrEmpty(accessToken)) //if there is no access_token user need to log in
        {
            return new JsonResult(new { status = "error", message = "Please log in" });
        }

        var refreshToken = HttpContext.Session.GetString("refresh_token");

        if (IsTokenExpired()) //refresh token if expire
        {
            if (string.IsNullOrEmpty(refreshToken))
            {
                return new JsonResult(new { status = "error", message = "You need to log in to Spotify to continue" });
            }

            var refreshedToken = await RefreshToken(refreshToken);
            HttpContext.Session.SetString("refresh_token", refreshedToken.RefreshToken);
            HttpContext.Session.SetString("access_token", refreshedToken.AccessToken);
            HttpContext.Session.SetString("access_token_creation_date",
                refreshedToken.CreatedAt.ToString(CultureInfo.CurrentCulture));
            spotifyClient = spotifyService.GetClient(refreshedToken.AccessToken);
        }
        else
        {
            spotifyClient = spotifyService.GetClient(accessToken);
        }


        try
        {
            var user = await spotifyClient.UserProfile.Current();
            var playlist = await spotifyClient.Playlists.Create(user.Id, new PlaylistCreateRequest(userInput));

            var tracksName = GetTracksName(completions);
            var trackUris = new List<string>();

            foreach (var trackName in tracksName)
            {
                var searchRequest = new SearchRequest(SearchRequest.Types.Track, trackName);
                var searchResponse = await spotifyClient.Search.Item(searchRequest);
                var trackId = searchResponse.Tracks.Items?.FirstOrDefault()?.Id;

                if (trackId == null) continue;
                
                var trackUri = $"spotify:track:{trackId}";
                trackUris.Add(trackUri);
            }

            if (trackUris.Count > 0)
            {
                var playlistItemModel = new PlaylistAddItemsRequest(trackUris);
                var resp = await spotifyClient.Playlists.AddItems(playlist.Id, playlistItemModel);
            }
            else
            {
                return new JsonResult(new { status = "error", message = "No songs added to the playlist" });
            }
        }
        catch (Exception e)
        {
            TempData["error"] = "An error occured please try again";
            return new JsonResult(new { status = "error", message = e.Message });
        }


        return new JsonResult(new { status = "success", message = "Playlist created successfully!" });
    }

    public RedirectResult Login()
    {
        var (verifier, challenge) = PKCEUtil.GenerateCodes();

        HttpContext.Session.SetString("verifier", verifier);

        var loginRequest = new LoginRequest(
            new Uri(_callbackUri),
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
            /*var httpClient = proxyHelper.ConfigureSpotifyProxyWithoutToken();
            var oAuthClient = env.IsDevelopment() ? new OAuthClient() : new OAuthClient(httpClient);*/

            var oAuthClient = new OAuthClient();
            
            // Note that we use the verifier calculated above!
            var initialResponse = await oAuthClient.RequestToken(
                new PKCETokenRequest("f4cd70cc16604aaf99eae2801a16a949", code,
                    new Uri(_callbackUri), verifier)
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
        /*var createdAt = initialResponseCreatedAt.ToUniversalTime().ToString(CultureInfo.InvariantCulture);
        var createdAtUtc = DateTime.Parse(createdAt);*/

        // Ottieni il fuso orario locale
        var localTimeZone = TimeZoneInfo.Local;

        // Converte createdAt al fuso orario locale
        DateTime createdAtLocal = TimeZoneInfo.ConvertTimeFromUtc(initialResponseCreatedAt, localTimeZone);

        HttpContext.Session.SetString("access_token_creation_date",
            createdAtLocal.ToString(CultureInfo.CurrentCulture));
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
}