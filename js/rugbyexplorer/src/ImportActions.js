import { useState, useEffect } from "react";
import { Button, Space, Popconfirm, notification, Card, Spin } from "antd";

const ImportActions = () => {
  const [loading, setLoading] = useState(false);
  const [loading2, setLoading2] = useState(false);
  const [deleting, setDeleting] = useState(false);
  const [eventStatus, setEventStatus] = useState([]);
  const [processedTeam, setProcessedTeam] = useState(0);
  const [totalTeams, setTotalTeams] = useState(0);

  const [api, contextHolder] = notification.useNotification();
  const openNotificationWithIcon = (type, msg, desc) => {
    api[type]({
      message: msg,
      description: desc
    });
  };

  const importData = async () => {
    try {
      setLoading(true);
      setEventStatus([]);
      // const response = await fetch(rugbyexplorer_params.ajax_url, {
      //   method: "POST",
      //   headers: {
      //     "X-WP-Nonce": rugbyexplorer_params.nonce,
      //     "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8"
      //   },
      //   body: new URLSearchParams({
      //     action: "rugbyexplorer_api"
      //   })
      // });

      // if (!response.ok) throw new Error("API request failed");
      // const { status, data } = await response.json();

      // console.log(data);
      // if (status === "success") {
      //   openNotificationWithIcon(
      //     "success",
      //     "Import Successful!",
      //     "Competitions imported successfully!"
      //     // data?.["rugby-schedule"]?.[0]?.["competitions"]?.[0]?.["full_name"]
      //   );
      // }

      // const allRequests = Object.entries(
      //   rugbyexplorer_params?.settings?.rugbyexplorer_field_club_teams
      // ).reduce(async (promise, [key, team]) => {
      //   return promise.then(async () => {
      //     const formData = new FormData();
      //     formData.append("action", "rugbyexplorer_api");
      //     formData.append("season", team?.season);
      //     formData.append("competition_id", team?.competition_id);
      //     formData.append("team_id", team?.team_id);
      //     formData.append("entity_id", team?.entity_id);

      //     const res = await fetch(rugbyexplorer_params.ajax_url, {
      //       method: "POST",
      //       headers: {
      //         "X-WP-Nonce": rugbyexplorer_params.nonce
      //       },
      //       body: formData
      //     });
      //     const data = await res.json();
      //     if (data.status === "success") {
      //       console.log(eventStatus);
      //       setEventStatus((prev) => [
      //         ...prev,
      //         { name: team?.name, stats: data?.data?.event_status }
      //       ]);
      //     }
      //   });
      // }, Promise.resolve());

      // // 👇 Detect when all are done
      // allRequests.then(() => {
      //   setLoading(false);
      // });

      const clubs =
        rugbyexplorer_params?.settings?.rugbyexplorer_field_club_teams || [];
      const sorted = clubs.sort(
        (a, b) =>
          Number(a.season) - Number(b.season) ||
          a.competition_id.localeCompare(b.competition_id)
      );
      setTotalTeams(clubs.length);
      const teams = Object.entries(sorted || []);

      for (const [key, team] of teams) {
        try {
          let skip = 0;
          let event_status = [];
          while (true) {
            const formData = new FormData();
            formData.append("action", "rugbyexplorer_api");
            formData.append("season", team?.season);
            formData.append("competition_id", team?.competition_id);
            formData.append("team_id", team?.team_id);
            formData.append("entity_id", team?.entity_id);
            formData.append("entity_type", team?.entity_type);
            formData.append("skip", skip);

            const res = await fetch(rugbyexplorer_params.ajax_url, {
              method: "POST",
              headers: { "X-WP-Nonce": rugbyexplorer_params.nonce },
              body: formData
            });

            const data = await res.json();
            console.log(data);
            if (data?.data?.total == 0 || data?.status !== "success") {
              break;
            }

            // check next batch
            event_status.push(data?.data?.event_status);
            skip += 20;
          }

          setProcessedTeam((prev) => prev + 1);
          const total = event_status.reduce((acc, curr) => {
            Object.keys(curr).forEach((key) => {
              acc[key] = (acc[key] || 0) + curr[key];
            });
            return acc;
          }, {});

          // convert seconds → minutes
          total.time = total?.time?.toFixed(2);

          setEventStatus((prev) => [
            ...prev,
            { name: team?.name, stats: total }
          ]);
        } catch (err) {
          console.error("Error fetching team", team?.name, err);
        }
      }

      setLoading(false);
    } catch (error) {
      console.error("Error fetching orders:", error);
    }
  };

  const cacheGamePlayeds = async () => {
    try {
      setLoading2(true);
      const formData = new FormData();
      formData.append("action", "cache_total_games_played");

      const res = await fetch(rugbyexplorer_params.ajax_url, {
        method: "POST",
        headers: { "X-WP-Nonce": rugbyexplorer_params.nonce },
        body: formData
      });

      const data = await res.json();
      console.log(data);
      setLoading2(false);
    } catch (error) {
      console.error("Error fetching orders:", error);
    }
  };

  const deleteEvents = () => {
    async function deleting() {
      try {
        setDeleting(true);

        const response = await fetch(rugbyexplorer_params.ajax_url, {
          method: "POST",
          headers: {
            "X-WP-Nonce": rugbyexplorer_params.nonce,
            "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8"
          },
          body: new URLSearchParams({
            action: "delete_events"
          })
        });

        if (!response.ok) throw new Error("API request failed");
        const { status, data } = await response.json();

        if (status === "success") {
          openNotificationWithIcon(
            "success",
            "Deletion Successful!",
            "Events, teams, seasons, leagues and venues have been successfully!"
            // data?.["rugby-schedule"]?.[0]?.["competitions"]?.[0]?.["full_name"]
          );
        }
      } catch (error) {
        console.error("Error fetching orders:", error);
      } finally {
        setDeleting(false);
      }
    }

    deleting();
  };

  return (
    <>
      <Card size="small" title="Import" style={{ marginBottom: "16px" }}>
        <Space size="large" direction="vertical">
          {contextHolder}
          <div>
            <Button onClick={importData} loading={loading}>
              Import Data
            </Button>
            <p>
              Import data from RugbyExplorer API to SportsPress. Import Fixtures
              and Results.
            </p>
          </div>
          <div>
            <Button onClick={cacheGamePlayeds} loading={loading2}>
              Cache Games Played Per Player
            </Button>
            <p>
              Loop all players and cache total games played for the whole
              career.
            </p>
          </div>
          <div>
            <Popconfirm
              title="Delete all events"
              description="Are you sure to delete all events, teams, seasons, leagues and venues?"
              onConfirm={deleteEvents}
              okText="Yes"
              cancelText="No"
            >
              <Button loading={deleting}>Delete Events</Button>
            </Popconfirm>

            <p>
              Delete all events, teams, seasons, leagues and venues. Use this if
              you want to import again.
            </p>
          </div>
        </Space>
      </Card>
      <ul>
        {eventStatus.map((status, index) => (
          <li key={index}>
            <strong>{`${index + 1}.) ${status.name}`}</strong> (Created:{" "}
            <b>{status?.stats?.created}</b>, Updated:{" "}
            <b>{status?.stats?.updated}</b>, Failed:{" "}
            <b>{status?.stats?.failed}</b>, Time Taken:{" "}
            <b>{status?.stats?.time}s</b>)
          </li>
        ))}
      </ul>
      <p>
        {loading && (
          <>
            <Spin spinning={loading} />{" "}
            {`${processedTeam} out of ${totalTeams} team(s) processed...`}
          </>
        )}
      </p>
    </>
  );
};
export default ImportActions;
