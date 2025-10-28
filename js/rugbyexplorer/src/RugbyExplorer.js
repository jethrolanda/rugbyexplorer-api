import { useEffect, useState } from "react";
import { CloseOutlined } from "@ant-design/icons";
import {
  Button,
  Card,
  Form,
  Input,
  Space,
  Typography,
  notification,
  Select,
  Row,
  Col
} from "antd";
import ImportActions from "./ImportActions";

const App = () => {
  const [form] = Form.useForm();
  const [loading, setLoading] = useState(false);

  const [api, contextHolder] = notification.useNotification();
  const openNotificationWithIcon = (type) => {
    api[type]({
      message: "Settings saved!",
      description: "Settings succesfully saved."
    });
  };

  const onFinish = async (values) => {
    setLoading(true);
    const formData = new FormData();
    formData.append("action", "save_settings");
    formData.append("data", JSON.stringify(values));

    const response = await fetch(rugbyexplorer_params.ajax_url, {
      method: "POST",
      headers: {
        "X-WP-Nonce": rugbyexplorer_params.nonce
      },
      body: formData
    });

    if (!response.ok) throw new Error("API request failed");
    const { status, data } = await response.json();
    setLoading(false);

    if (status === "success") {
      openNotificationWithIcon("success");
    }
  };
  useEffect(() => {
    form.setFieldsValue({
      sportspress_field_api_username:
        rugbyexplorer_params?.settings?.sportspress_field_api_username ?? "",
      sportspress_field_api_password:
        rugbyexplorer_params?.settings?.sportspress_field_api_password ?? "",
      rugbyexplorer_field_schedule_update:
        rugbyexplorer_params?.settings?.rugbyexplorer_field_schedule_update ??
        "daily",
      rugbyexplorer_field_club_teams:
        rugbyexplorer_params?.settings?.rugbyexplorer_field_club_teams ?? []
    });
  }, []);

  return (
    <>
      <Row gutter={16}>
        <Col xs={24} md={14}>
          <Form
            labelCol={{ span: 6 }}
            wrapperCol={{ span: 18 }}
            form={form}
            name="dynamic_form_complex"
            // style={{ maxWidth: 900 }}
            autoComplete="off"
            // initialValues={{
            //   sportspress_field_api_username:
            //     rugbyexplorer_params?.sportspress_field_api_username ?? "",
            //   sportspress_field_api_password:
            //     rugbyexplorer_params?.sportspress_field_api_password ?? "",
            //   rugbyexplorer_field_schedule_update:
            //     rugbyexplorer_params?.rugbyexplorer_field_schedule_update ?? "",
            //   rugbyexplorer_field_club_teams:
            //     rugbyexplorer_params?.sportspress_field_api_username ?? []
            // }}
            onFinish={onFinish}
          >
            {contextHolder}
            <Card size="small" title="General" style={{ marginBottom: "16px" }}>
              <Form.Item
                label="SportsPress API Username"
                name="sportspress_field_api_username"
                rules={[
                  {
                    required: true,
                    message: "Please input SportsPress API Username!"
                  }
                ]}
              >
                <Input />
              </Form.Item>
              <Form.Item
                label="SportsPress API Password"
                name="sportspress_field_api_password"
                rules={[
                  {
                    required: true,
                    message: "Please input SportsPress API Password!"
                  }
                ]}
                hasFeedback
              >
                <Input.Password />
              </Form.Item>
              <Form.Item
                label="Schedule Update"
                name="rugbyexplorer_field_schedule_update"
              >
                <Select
                  // onChange={handleChange}
                  options={[
                    { value: "daily", label: "Daily" },
                    { value: "weekly", label: "Weekly" },
                    { value: "every_fifteen_minutes", label: "Every 15 Mins" }
                  ]}
                />
              </Form.Item>
            </Card>

            {/* <Form.Item> */}
            <Form.List name="rugbyexplorer_field_club_teams">
              {(fields, { add, remove }) => (
                <div
                  style={{
                    display: "flex",
                    rowGap: 16,
                    flexDirection: "column"
                  }}
                >
                  {fields.map((field) => (
                    <Card
                      size="small"
                      title={`Team  ${field.name + 1}`}
                      key={field.key}
                      extra={
                        <CloseOutlined
                          onClick={() => {
                            remove(field.name);
                          }}
                        />
                      }
                    >
                      <Form.Item
                        label="Name"
                        name={[field.name, "name"]}
                        rules={[
                          { required: true, message: "Please input team name!" }
                        ]}
                      >
                        <Input />
                      </Form.Item>
                      <Form.Item
                        label="Season"
                        name={[field.name, "season"]}
                        tooltip={() => "Ex: 2025"}
                        rules={[
                          {
                            required: true,
                            message: "Please input season year!"
                          }
                        ]}
                      >
                        <Input />
                      </Form.Item>
                      <Form.Item
                        label="Entity ID"
                        name={[field.name, "entity_id"]}
                        tooltip={() =>
                          "Ex: jruc, jjruc, scm-jnr-rugby-union, etc."
                        }
                        rules={[
                          { required: true, message: "Please input entity ID!" }
                        ]}
                      >
                        <Input />
                      </Form.Item>
                      <Form.Item
                        label="Competition ID"
                        name={[field.name, "competition_id"]}
                        rules={[
                          {
                            required: true,
                            message: "Please input competition ID!"
                          }
                        ]}
                      >
                        <Input />
                      </Form.Item>
                      <Form.Item
                        label="Team ID"
                        name={[field.name, "team_id"]}
                        rules={[
                          { required: true, message: "Please input team IDq!" }
                        ]}
                      >
                        <Input />
                      </Form.Item>

                      {/* Nest Form.List */}
                      <Form.Item label="List" style={{ display: "none" }}>
                        <Form.List name={[field.name, "list"]}>
                          {(subFields, subOpt) => (
                            <div
                              style={{
                                display: "flex",
                                flexDirection: "column",
                                rowGap: 16
                              }}
                            >
                              {subFields.map((subField) => (
                                <Space key={subField.key}>
                                  <Form.Item
                                    noStyle
                                    name={[subField.name, "first"]}
                                  >
                                    <Input placeholder="first" />
                                  </Form.Item>
                                  <Form.Item
                                    noStyle
                                    name={[subField.name, "second"]}
                                  >
                                    <Input placeholder="second" />
                                  </Form.Item>
                                  <CloseOutlined
                                    onClick={() => {
                                      subOpt.remove(subField.name);
                                    }}
                                  />
                                </Space>
                              ))}
                              <Button
                                type="dashed"
                                onClick={() => subOpt.add()}
                                block
                              >
                                + Add Sub Item
                              </Button>
                            </div>
                          )}
                        </Form.List>
                      </Form.Item>
                    </Card>
                  ))}

                  <Button type="dashed" onClick={() => add()} block>
                    + Add Team
                  </Button>
                </div>
              )}
            </Form.List>
            {/* </Form.Item> */}
            <Form.Item noStyle shouldUpdate hidden>
              {() => (
                <Typography>
                  <pre>{JSON.stringify(form.getFieldsValue(), null, 2)}</pre>
                </Typography>
              )}
            </Form.Item>
            <Form.Item style={{ marginTop: "16px" }}>
              <Button type="primary" htmlType="submit" loading={loading}>
                Submit
              </Button>
            </Form.Item>
          </Form>
        </Col>

        <Col xs={24} md={10}>
          <ImportActions />
        </Col>
      </Row>
    </>
  );
};
export default App;
