using System.Diagnostics;
using System.Globalization;
using System.Text.Json;
using System.Text.RegularExpressions;
using Microsoft.AspNetCore.Mvc;
using Moodify.Helpers;
using Moodify.Models;
using OpenAI.Chat;
using SpotifyAPI.Web;

namespace Moodify.Controllers;

public class HomeController(ILogger<HomeController> logger, IConfiguration configuration, CategoriesHelper categoriesHelper) : BaseController
{
    private readonly string _chatGptApiKey = configuration.GetSection("Api:SecretKey").Value ?? "";

    public IActionResult Index()
    {
        return View();
    }

    [HttpPost]
    public IActionResult GetCategories(string request)
    {
        TempData.Clear();
        ChatClient client = new(model: "gpt-4o-mini", _chatGptApiKey);

        var formattedRequest =
            $"Give me in english one time of the day, one mood, one relationship and one musical taste which are related to : \\\"{request}\\\" separated by comma";

        var completion = client.CompleteChat(formattedRequest).Value.ToString();

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
            homeViewModel.DayTimesDictionary.Add(dayTime, dayTime.Equals(categories.ElementAt(0), StringComparison.OrdinalIgnoreCase));
        }
        
        foreach (var mood in moods)
        {
            homeViewModel.MoodsDictionary.Add(mood, mood.Equals(categories.ElementAt(1), StringComparison.OrdinalIgnoreCase));
        }
        
        foreach (var relationship in relationships)
        {
            homeViewModel.RelationshipsDictionary.Add(relationship, relationship.Equals(categories.ElementAt(2), StringComparison.OrdinalIgnoreCase));
        }
        
        foreach (var musicalTaste in musicalTastes)
        {
            homeViewModel.MusicalTastesDictionary.Add(musicalTaste, musicalTaste.Equals(categories.ElementAt(3), StringComparison.OrdinalIgnoreCase));
        }

        
        return View("Index", homeViewModel);
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
    public async Task<JsonResult> CreatePlaylist(string completions, string userInput)
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
            new Uri("https://localhost:7206/home/callback"),
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
                    new Uri("https://localhost:7206/home/callback"), verifier)
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
        
        HttpContext.Session.SetString("access_token_creation_date", createdAtLocal.ToString(CultureInfo.InvariantCulture));
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