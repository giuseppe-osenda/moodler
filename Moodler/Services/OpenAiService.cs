using System.ClientModel;
using System.ClientModel.Primitives;
using System.Net;
using Moodler.Helpers;
using OpenAI;
using OpenAI.Chat;

namespace Moodler.Services;

public class OpenAiService(EncryptHelper encryptHelper, IWebHostEnvironment env, IConfiguration configuration, ProxyHelper proxyHelper) : IOpenAiService
{
    private readonly string _chatGptApiKey = configuration["OpenAi"] ?? "";

        
    
    public ChatClient GetChatClient()
    {
        var apiKeyCredential = new ApiKeyCredential(_chatGptApiKey);

        return new ChatClient(model: "gpt-4o-mini", apiKeyCredential);
    }

    private OpenAIClientOptions SetProxyOptions()
    {
        var httpClient = proxyHelper.ConfigureProxy();

        OpenAIClientOptions options = new()
        {
            Transport = new HttpClientPipelineTransport(httpClient),
        };

        return options;
    }
}